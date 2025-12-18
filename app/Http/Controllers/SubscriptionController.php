<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Invoice;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:chihuahua,labrador,mastiff',
            'billing' => 'required|in:monthly,annual',
        ]);

        $user = $request->user();
        $newPlan = $request->plan;
        $billingCycle = $request->billing;

        // Determine price
        $price = 0;
        $description = "";
        
        if ($newPlan === 'labrador') {
            $price = ($billingCycle === 'annual') ? 490 : 49;
            $description = "Labrador Plan - " . ucfirst($billingCycle);
        } elseif ($newPlan === 'mastiff') {
            $price = ($billingCycle === 'annual') ? 990 : 99;
            $description = "Mastiff Plan - " . ucfirst($billingCycle);
        } elseif ($newPlan === 'chihuahua') {
            $price = 0;
            $description = "Chihuahua Plan (Free) - " . ucfirst($billingCycle);
        }

        // Logic:
        // 1. Charge the user (mocked - we just create invoice)
        // 2. Update user plan
        // 3. Set next billing date

        $user->plan = $newPlan;
        $user->plan_status = 'active';
        // Simple logic: +30 days for monthly, +365 for annual
        $user->next_billing_date = ($billingCycle === 'annual') 
            ? Carbon::now()->addYear() 
            : Carbon::now()->addMonth();
        
        $user->save();

        // Generate Invoice
        Invoice::create([
            'user_id' => $user->id,
            'amount' => $price,
            'status' => 'paid',
            'description' => $description,
            'invoice_url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf' // Mock valid PDF for testing
        ]);

        return response()->json([
            'message' => "Successfully subscribed to $newPlan plan.",
            'user' => $user
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        // Revert to free immediately for this prototype
        // In real world, we might let them finish the cycle.
        $user->plan = 'chihuahua'; // or 'free'
        $user->plan_status = 'cancelled';
        $user->next_billing_date = null;
        $user->save();

        return response()->json([
            'message' => 'Subscription cancelled. You are now on the Free plan.',
            'user' => $user
        ]);
    }
}
