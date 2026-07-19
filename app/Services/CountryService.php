<?php

namespace app\Services;

use app\Models\Country;
use app\Models\State;

/**
 * Country service class
 */
class CountryService
{

    public function countries()
    {
        $allowed = config('countries.allowed');
        $key = 'countries_' . implode('_', $allowed);
        $countries = cache()->remember($key, 60 * 5, function () use($allowed) {
            // return Country::with(['states'])->whereIn("name", $allowed)->get();
            return Country::orderBy("name")->get();
        });
        return $countries;
    }

    public function getCountry($id)
    {
        return Country::find($id);
    }

    public function getStates($countryId)
    {
        // dd($countryId);
        return State::where("country_id", $countryId)->orderBy("name")->get();
    }

    public function getState($id)
    {
        return State::find($id);
    }

    public function addState($countryId, $state)
    {
        return State::firstOrCreate(["country_id" => $countryId, "name" => $state]);
    }

}
