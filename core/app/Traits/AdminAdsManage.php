<?php

namespace App\Traits;

use App\Models\Advertisement;

trait AdminAdsManage
{
    protected $view;

    public function index($id = null)
    {

        $pageTitle      = "All Advertisement";
        $advertisements = $this->advertisementData(userId: $id);
        return view('admin.' . $this->view . '.index', compact('advertisements', 'pageTitle'));
    }


    public function pending($id = null)
    {
        $pageTitle      = "Pending Advertisement";
        $advertisements = $this->advertisementData('pending', $id);

        return view('admin.' . $this->view . '.index', compact('advertisements', 'pageTitle'));
    }

    public function rejected($id = null)
    {
        $pageTitle      = "Rejected Advertisement";
        $advertisements = $this->advertisementData('rejected', $id);

        return view('admin.' . $this->view . '.index', compact('advertisements', 'pageTitle'));
    }


    public function running($id = null)
    {
        $pageTitle      = "Running Ad";
        $advertisements = $this->advertisementData('running', $id);

        return view('admin.' . $this->view . '.index', compact('advertisements', 'pageTitle'));
    }

    public function pause($id = null)
    {
        $pageTitle      = "Pause Ad";
        $advertisements = $this->advertisementData('stop', $id);
        return view('admin.' . $this->view . '.index', compact('advertisements', 'pageTitle'));
    }



    public function expired($id = null)
    {
        $pageTitle      = "Expired Ad";
        $advertisements = $this->advertisementData('expired', $id);
        return view('admin.' . $this->view . '.index', compact('advertisements', 'pageTitle'));
    }


    protected function advertisementData($scope = null, $userId = null)
    {
        if ($scope) {
            $advertisements = Advertisement::$scope();
        } else {
            $advertisements = Advertisement::query();
        }

        if ($userId) {
            $advertisements = $advertisements->where('user_id', $userId);
        }
        $advertisements = $advertisements
            ->searchable(['title', 'user:username'])

            ->with('user', 'campaign')->orderBy('id', 'desc')

            ->paginate(getPaginate());

        return $advertisements;
    }
}
