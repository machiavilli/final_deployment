<?php

namespace App\Controller;

use App\Service\AdminDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(AdminDashboardService $dashboardService): Response
    {
        return $this->render('admin/dashboard.html.twig', $dashboardService->getDashboardData());
    }

    #[Route('/dashboard/sync', name: 'app_admin_dashboard_sync', methods: ['GET'])]
    public function dashboardSync(AdminDashboardService $dashboardService): JsonResponse
    {
        return $this->json($dashboardService->buildSyncPayload());
    }
}
