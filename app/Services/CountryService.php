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
            return Country::with(['states'])->whereIn("name", $allowed)->get();
        });
        return $countries;
    }

    public function getCountry($id)
    {
        return COuntry::find($id);
    }

    public function getState($id)
    {
        return State::find($id);
    }

}
