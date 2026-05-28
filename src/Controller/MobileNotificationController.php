<?php

namespace App\Controller;

use App\DTO\ApiResponse;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/mobile/notifications')]
#[IsGranted('ROLE_USER')]
class MobileNotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'api_mobile_notifications_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->requireUser();
        $items = $this->notificationRepository->findForUser($user);
        $unread = $this->notificationRepository->countUnreadForUser($user);

        $data = array_map(fn (Notification $n) => $this->serialize($n), $items);

        $response = ApiResponse::success('Notifications retrieved', $data, [
            'unread_count' => $unread,
            'total' => count($data),
        ]);

        return $this->json($response->toArray());
    }

    #[Route('/poll', name: 'api_mobile_notifications_poll', methods: ['GET'])]
    public function poll(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        $sinceId = max(0, (int) $request->query->get('since_id', 0));
        $items = $this->notificationRepository->findForUserSinceId($user, $sinceId);
        $data = array_map(fn (Notification $n) => $this->serialize($n), $items);

        $response = ApiResponse::success('New notifications', $data, [
            'since_id' => $sinceId,
            'count' => count($data),
        ]);

        return $this->json($response->toArray());
    }

    #[Route('/unread-count', name: 'api_mobile_notifications_unread', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $user = $this->requireUser();
        $count = $this->notificationRepository->countUnreadForUser($user);

        $response = ApiResponse::success('Unread count retrieved', [
            'unread_count' => $count,
        ]);

        return $this->json($response->toArray());
    }

    #[Route('/read-all', name: 'api_mobile_notifications_read_all', methods: ['PATCH', 'PUT'])]
    public function markAllRead(): JsonResponse
    {
        $user = $this->requireUser();
        $items = $this->notificationRepository->findForUser($user, 200);

        foreach ($items as $notification) {
            $notification->setIsRead(true);
        }
        $this->entityManager->flush();

        $response = ApiResponse::success('All notifications marked as read', [
            'marked' => count($items),
        ]);

        return $this->json($response->toArray());
    }

    #[Route('/{id}/read', name: 'api_mobile_notifications_read', methods: ['PATCH', 'PUT'], requirements: ['id' => '\d+'])]
    public function markRead(int $id): JsonResponse
    {
        $user = $this->requireUser();
        $notification = $this->notificationRepository->find($id);

        if (!$notification || $notification->getUser() !== $user) {
            $response = ApiResponse::error('Notification not found');
            return $this->json($response->toArray(), 404);
        }

        $notification->setIsRead(true);
        $this->entityManager->flush();

        $response = ApiResponse::success('Notification marked as read', $this->serialize($notification));

        return $this->json($response->toArray());
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function serialize(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'type' => $notification->getType(),
            'order_id' => $notification->getOrderId(),
            'order_number' => $notification->getOrderNumber(),
            'is_read' => $notification->isRead(),
            'created_at' => $notification->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
