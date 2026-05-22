<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff')]
#[IsGranted('ROLE_STAFF')]
final class StaffDashboardController extends AbstractController
{
    #[Route('/', name: 'app_staff_home', methods: ['GET'])]
    public function index(ActivityLogRepository $activityLogRepository): Response
    {
        return $this->render('staff/index.html.twig', [
            'recentLogs' => $activityLogRepository->findRecent(5),
        ]);
    }
}

