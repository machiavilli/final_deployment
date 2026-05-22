<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_USER')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard')]
    public function dashboard(
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        // Get basic totals only (temporarily simplified to avoid timeout)
        $totalUsers = 0;
        $totalProducts = 0;
        $totalOrders = 0;
        
        try {
            $totalUsers = $userRepository->count([]);
            $totalProducts = $em->getRepository(Product::class)->count([]);
            $totalOrders = $em->getRepository(Order::class)->count([]);
        } catch (\Exception $e) {
            // Log error but continue with zeros
            error_log('Dashboard query error: ' . $e->getMessage());
        }

        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
        ]);
    }
}

