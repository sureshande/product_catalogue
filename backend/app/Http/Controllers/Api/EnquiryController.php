<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Services\EnquiryService;
use Illuminate\Http\JsonResponse;

class EnquiryController extends Controller
{
    /** POST /api/enquiries — 202: saved, email queued. */
    public function store(StoreEnquiryRequest $request, EnquiryService $enquiries): JsonResponse
    {
        $enquiry = $enquiries->submit($request->safe()->except('website'));

        return response()->json([
            'id' => $enquiry->id,
            'message' => 'Thank you! Our sales team will be in touch soon.',
        ], 202);
    }
}
