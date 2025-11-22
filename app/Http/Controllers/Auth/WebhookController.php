<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle incoming webhook events from Genuka.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Get the event data
            $event = $request->all();

            // Validate webhook signature if provided
            $this->validateWebhookSignature($request);

            // Process the webhook event based on type
            $this->processWebhookEvent($event);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process webhook',
            ], 500);
        }
    }

    /**
     * Validate webhook signature for security.
     *
     * @throws \Exception
     */
    protected function validateWebhookSignature(Request $request): void
    {
        $signature = $request->header('X-Genuka-Signature');

        if (! $signature) {
            // If signature validation is optional, you can remove this exception
            Log::warning('Webhook received without signature');

            return;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, config('genuka.client_secret'));

        if (! hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }
    }

    /**
     * Process webhook event based on type.
     */
    protected function processWebhookEvent(array $event): void
    {
        $eventType = $event['type'] ?? null;

        match ($eventType) {
            'company.updated' => $this->handleCompanyUpdated($event),
            'company.deleted' => $this->handleCompanyDeleted($event),
            'subscription.created' => $this->handleSubscriptionCreated($event),
            'subscription.updated' => $this->handleSubscriptionUpdated($event),
            'subscription.cancelled' => $this->handleSubscriptionCancelled($event),
            'payment.succeeded' => $this->handlePaymentSucceeded($event),
            'payment.failed' => $this->handlePaymentFailed($event),
            default => $this->handleUnknownEvent($event),
        };
    }

    /**
     * Handle company updated event.
     */
    protected function handleCompanyUpdated(array $event): void
    {
        Log::info('Company updated event', $event);

        // TODO: Implement company update logic
        // Example: Update company information in database
    }

    /**
     * Handle company deleted event.
     */
    protected function handleCompanyDeleted(array $event): void
    {
        Log::info('Company deleted event', $event);

        // TODO: Implement company deletion logic
        // Example: Soft delete or remove company from database
    }

    /**
     * Handle subscription created event.
     */
    protected function handleSubscriptionCreated(array $event): void
    {
        Log::info('Subscription created event', $event);

        // TODO: Implement subscription creation logic
    }

    /**
     * Handle subscription updated event.
     */
    protected function handleSubscriptionUpdated(array $event): void
    {
        Log::info('Subscription updated event', $event);

        // TODO: Implement subscription update logic
    }

    /**
     * Handle subscription cancelled event.
     */
    protected function handleSubscriptionCancelled(array $event): void
    {
        Log::info('Subscription cancelled event', $event);

        // TODO: Implement subscription cancellation logic
    }

    /**
     * Handle payment succeeded event.
     */
    protected function handlePaymentSucceeded(array $event): void
    {
        Log::info('Payment succeeded event', $event);

        // TODO: Implement payment success logic
    }

    /**
     * Handle payment failed event.
     */
    protected function handlePaymentFailed(array $event): void
    {
        Log::info('Payment failed event', $event);

        // TODO: Implement payment failure logic
    }

    /**
     * Handle unknown event type.
     */
    protected function handleUnknownEvent(array $event): void
    {
        Log::warning('Unknown webhook event type', $event);
    }
}
