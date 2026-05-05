<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Campaign extends Model
{
    use GlobalStatus;

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }


    public function campaignStatus(): Attribute
    {

         return new Attribute(function(){
            $html = '';
            if($this->status == Status::ENABLE){
                $html = '<span class="badge badge--success">'.trans('Enable').'</span>';
            }
            elseif($this->status == Status::DISABLE){
                $html = '<span><span class="badge badge--danger">'.trans('Disable').'</span>';
            }
            return $html;
        });
    }

     public function campaignPaymentStatus(): Attribute
    {

         return new Attribute(function(){
            $html = '';
            if($this->payment_status == Status::PAYMENT_SUCCESS){
                $html = '<span class="badge badge--success">'.trans('Success').'</span>';
            }
            elseif($this->payment_status == Status::PAYMENT_PENDING){
                $html = '<span><span class="badge badge--warning">'.trans('Pending').'</span>';
            }elseif($this->payment_status == Status::PAYMENT_INITIATE){
                $html = '<span><span class="badge badge--dark">'.trans('Initiated').'</span>';
            }   
            return $html;
        });
    }

}
