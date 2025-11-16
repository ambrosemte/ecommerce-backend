<?php

namespace App\Http\Controllers;

use App\Helpers\Response;
use App\Models\City;
use App\Models\Country;
use App\Models\State;

class CscController extends Controller
{
    public function getCountries()
    {
        $data = Country::all()->toArray();

        return Response::success(message: "Country list retireved", data: $data);
    }

    public function getStates($countryId)
    {
        $data = State::where('country_id', $countryId)->get()->toArray();

        return Response::success(message: "State list retireved", data: $data);
    }

    public function getCities($stateId)
    {
        $data = City::where('state_id', $stateId)->get()->toArray();

        return Response::success(message: "City list retireved", data: $data);
    }
}
