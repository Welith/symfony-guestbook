<?php


namespace App\Controller;


use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

/**
 * Class AdminController
 * @package App\Controller
 *
 * @Route("/admin")
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
     * @Route("/comment/review/{id}", name="review_comment")
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

    /**
     * @param KernelInterface $kernel
     * @param Request $request
     * @param string $uri
     * @return Response
     * @Route("/http-cache/{uri<.*>}", methods={"PURGE"})
     */
    public function purgeHttpCache(KernelInterface $kernel, Request $request, string $uri)
    {
        if ('prod' === $kernel->getEnvironment()) {
            return new Response('KO', 400);
        }

        $store = (new class($kernel) extends  HttpCache {})->getStore();
        $store->purge($request->getSchemeAndHttpHost().'/'.$uri);
        return new Response('Done.');
    }
}