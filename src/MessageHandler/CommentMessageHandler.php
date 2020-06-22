<?php


namespace App\MessageHandler;


use App\ImageResizer;
use App\Message\CommentMessage;
use App\Notification\CommentReviewNotification;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunSmtpTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private const MAILGUN_USER = 'postmaster@sandbox0db57def883c4d65a4714b963af91b17.mailgun.org';
    private const MAILGUN_PASS = 'ee4c61452fe42d802e31aca5cb4ae818-1b6eb03d-be80a194';

    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $bus;
    private $workflow;
    private $logger;
    private $notifier;
    private $imageResizer;
    private $photoDir;

    public function __construct(EntityManagerInterface $entityManager,
                                SpamChecker $spamChecker,
                                CommentRepository $commentRepository,
                                MessageBusInterface $bus, WorkflowInterface $commentStateMachine,
                                NotifierInterface $notifier,
                                ImageResizer $imageResizer,
                                string $photoDir,
                                LoggerInterface $logger = null)
    {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->imageResizer = $imageResizer;
        $this->photoDir = $photoDir;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        $notification = new CommentReviewNotification($comment, $message->getReviewUrl());
        if (!$comment) {
            return;
        }

        if ($this->workflow->can($comment, 'might_be_spam')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = 'might_be_spam';
            $this->workflow->apply($comment, $transition);
            if (1 === $score) {
                $transition = 'reject';
                $this->workflow->apply($comment, $transition);
            }
            $this->entityManager->flush();
            $this->bus->dispatch($message);
        } elseif ($this->workflow->can($comment, 'publish')) {
            $this->notifier->send($notification, ...$this->notifier->getAdminRecipients());
        } elseif ($this->workflow->can($comment, 'optimize')) {
            if ($comment->getPhotoFilename()) {
                $this->imageResizer->resize($this->photoDir.'/'.$comment->getPhotoFilename());
            }
            $this->workflow->apply($comment, 'optimize');
            $this->entityManager->flush();

            # Notify user
            $email = (new Email())
                ->from('admin@guestbook.com')
                ->to($comment->getEmail())
                ->subject('Comment published')
                ->html('<p>Comment posted</p>');
            $transport = new MailgunSmtpTransport(self::MAILGUN_USER , self::MAILGUN_PASS);
            $mailer = new Mailer($transport);
            $mailer->send($email);
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState()
            ]);
        }
    }
}