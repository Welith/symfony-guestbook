<?php


namespace App\Controller;


use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

/**
 * Class AdminController
 * @package App\Controller
 */
class AdminController extends AbstractController
{
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var MessageBusInterface
     */
    private $bus;

    /**
     * AdminController constructor.
     * @param Environment $twig
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     */
    public function __construct(Environment $twig,
                                EntityManagerInterface $entityManager,
                                MessageBusInterface $bus)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    /**
     * @Route("/admin/comment/review/{id}", name="review_comment")
     * @param Request $request
     * @param Comment $comment
     * @param Registry $registry
     * @return Response
     */
    public function reviewComment(Request $request,
                                  Comment $comment,
                                  Registry $registry)
    {
        $accepted = !$request->query->get('reject');
        $machine = $registry->get($comment);
        if ($machine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } else {
            return new Response('Comment already reviewed or in wrong state.');
        }
        $machine->apply($comment, $transition);
        $this->entityManager->flush();
        if ($accepted) {
            $this->bus->dispatch(new CommentMessage($comment->getId()));
        }

        return $this->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment' => $comment
        ]);
    }
}