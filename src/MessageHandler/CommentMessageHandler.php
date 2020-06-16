<?php


namespace App\MessageHandler;


use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $bus;
    private $workflow;
    private $logger;
    private $mailer;
    private $adminEmail;

    public function __construct(EntityManagerInterface $entityManager,
                                SpamChecker $spamChecker,
                                CommentRepository $commentRepository,
                                MessageBusInterface $bus, WorkflowInterface $commentStateMachine,
                                LoggerInterface $logger = null,
                                MailerInterface $mailer, string $adminEmail)
    {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

//        if (1 === $this->spamChecker->getSpamScore($comment, $message->getContext())) {
//            $comment->setState('spam');
//        } else {
//            $comment->setState('published');
//        }
        if ($this->workflow->can($comment, 'might_be_spam')){
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
            $this->mailer->send((new NotificationEmail())
                ->subject('New Comment Posted!')
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->context(['comment' => $comment]));
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState()
            ]);
        }
    }
}