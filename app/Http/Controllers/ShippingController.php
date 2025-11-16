<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller
{

    public function getShippingOptions(string $deliveryDetailsId)
    {
        $user = User::find(Auth::id());
        $deliveryDetails = $user->deliveryDetails()->find($deliveryDetailsId);

        if (!$deliveryDetails) {
            return Response::notFound(message: "Delivery detail not found");
        }

        $country = $deliveryDetails->country;
        $state = $deliveryDetails->state;
        $city = $deliveryDetails->city;

        // Find a matching zone
        $zone = ShippingZone::where('country', $country)
            ->where(function ($q) use ($state) {
                $q->whereNull('state')->orWhere('state', $state);
            })
            ->where(function ($q) use ($city) {
                $q->whereNull('city')->orWhere('city', $city);
            })
            ->first();

        if (!$zone) {
            return Response::notFound(message: "No shipping options available for your location");
        }

        $rates = ShippingRate::with('method')
            ->where('shipping_zone_id', $zone->id)
            ->get()
            ->toArray();

        return Response::success(message: "Shipping options retrieved", data: $rates);
    }

    //ADMIN
    public function getShippingMethods()
    {
        $data = ShippingMethod::get()->toArray();

        return Response::success(message: "Shipping methods retrieved", data: $data);
    }

    public function getShippingZones()
    {
        $data = ShippingZone::select()->paginate(15)->toArray();

        return Response::success(message: "Shipping zones retrieved", data: $data);
    }

    public function getShippingRate($shippingZoneId = null)
    {
        $data = ShippingRate::select()
            ->with(['method', 'zone'])
            ->when($shippingZoneId, function ($query, $shippingZoneId) {
                return $query->where('shipping_zone_id', $shippingZoneId);
            })
            ->latest()
            ->paginate(15)
            ->toArray();

        return Response::success(message: "Shipping rates retrieved", data: $data);
    }

    public function createShippingMethod(Request $request)
    {
        $validated = $request->validate([
            'name' => "required|string|unique:shipping_methods,name"
        ]);

        ShippingMethod::create($validated);

        return Response::success(message: "Shipping method added");
    }

    public function createShippingZone(Request $request)
    {
        $validated = $request->validate([
            'name' => "required|string",
            'country' => "required|string",
            'state' => "required|string",
            'city' => "required|string",
        ]);

        $exists = ShippingZone::where('country', $validated['country'])
            ->where('state', $validated['state'])
            ->where('city', $validated['city'])
            ->exists();

        if ($exists) {
            return Response::error(message: 'This shipping zone already exists.');
        }

        ShippingZone::create($validated);

        return Response::success(message: "Shipping zone added");
    }

    public function createShippingRate(Request $request)
    {
        $validated = $request->validate([
            'shipping_method_id' => "required|string|exists:shipping_methods,id",
            'shipping_zone_id' => "required|string|exists:shipping_zones,id",
            'cost' => 'required|numeric|decimal:0,2',
            'days_max' => "required|numeric",
            'days_min' => "required|numeric",
        ]);

        $exists = ShippingRate::where('shipping_method_id', $validated['shipping_method_id'])
            ->where('shipping_zone_id', $validated['shipping_zone_id'])
            ->exists();

        if ($exists) {
            return Response::error(message: 'A shipping rate for this method and zone already exists.');
        }

        ShippingRate::create($validated);

        return Response::success(message: "Shipping rate added");
    }

    public function updateShippingMethod(Request $request, string $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $shippingMethod = ShippingMethod::find($id);

        if (!$shippingMethod) {
            return Response::notFound(message: "Shipping method not found");
        }

        $shippingMethod->update([
            'is_active' => $request['is_active'],
        ]);

        return Response::success(message: "Shipping method updated successfully");
    }

    public function updateShippingZone(Request $request, string $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $shippingZone = ShippingZone::find($id);

        if (!$shippingZone) {
            return Response::notFound(message: "Shipping zone not found");
        }

        $shippingZone->update([
            'is_active' => $request['is_active'],
        ]);

        return Response::success(message: "Shipping zone updated successfully");
    }


}
