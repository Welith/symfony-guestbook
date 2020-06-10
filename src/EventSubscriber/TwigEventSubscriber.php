<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;

/**
 * Class TwigEventSubscriber
 * @package App\EventSubscriber
 */
class TwigEventSubscriber implements EventSubscriberInterface
{
    private $twig;
    private $conferenceRepository;

    /**
     * TwigEventSubscriber constructor.
     * @param Environment $twig
     * @param ConferenceRepository $conferenceRepository
     */
    public function __construct(Environment $twig, ConferenceRepository $conferenceRepository)
    {
        $this->twig = $twig;
        $this->conferenceRepository = $conferenceRepository;
    }

    /**
     * @param ControllerEvent $event
     */
    public function onKernelController(ControllerEvent $event)
    {
        $this->twig->addGlobal('conferences', $this->conferenceRepository->findAll());
    }

    /**
     * @return array|string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.controller' => 'onKernelController',
        ];
    }
}
