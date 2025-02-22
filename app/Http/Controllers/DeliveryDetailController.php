<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryDetailController extends Controller
{
    public function index()
    {
        $data = Auth::user()->deliveryDetails()->get()->toArray();

        return Response::success(message: "Delivery details retrieved", data: $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            "contact_name" => "required|string|max:80",
            "address" => "required|string|max:255",
            "city" => "required|string|max:80",
            "state" => "required|string|max:80",
            "postcode" => "required|numeric|max:20",
            "country" => "required|string|max:80",
            "phone" => "required|numeric",
            "alternative_phone" => "nullable|numeric",
            "note" => "nullable|string|max:255",
            "is_default" => "required|boolean",
        ]);

        if ($request['is_default']) {
            Auth::user()->deliveryDetails()->update(['is_default' => false]);
        }

        Auth::user()->deliveryDetails()->create([
            "contact_name" => $request['contact_name'],
            "street_address" => $request['address'],
            'city' => $request['city'],
            'state' => $request['state'],
            'country' => $request['country'],
            'zip_code' => $request['postcode'],
            'phone' => $request['phone'],
            'alternative_phone' => $request['alternative_phone'],
            "note" => $request['note'],
            "is_default" => $request['is_default'],
        ]);

        return Response::success(message: "Delivery details added");

    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            "contact_name" => "required|string|max:80",
            "address" => "required|string|max:255",
            "city" => "required|string|max:80",
            "state" => "required|string|max:80",
            "postcode" => "required|numeric|max:20",
            "country" => "required|string|max:80",
            "phone" => "required|numeric",
            "alternative_phone" => "nullable|numeric",
            "note" => "nullable|string|max:255",
            "is_default" => "required|boolean",
        ]);

        if ($request['is_default']) {
            Auth::user()->deliveryDetails()->update(['is_default' => false]);
        }

        Auth::user()->deliveryDetails()->find($id)->update([
            "contact_name" => $request['contact_name'],
            "street_address" => $request['address'],
            'city' => $request['city'],
            'state' => $request['state'],
            'country' => $request['country'],
            'phone' => $request['phone'],
            'alternative_phone' => $request['alternative_phone'],
            "note" => $request['note'],
            "is_default" => $request['is_default'],
        ]);

        return Response::success(message: "Delivery details updated");

    }

    public function delete(string $id)
    {
        Auth::user()->deliveryDetails()->find($id)->delete();

        return Response::success(message: "Delivery details deleted");

    }
}
