<?php

namespace App;

trait HasUser
{
    public static function bootHasUser()
    {
        static::creating(function ($model) {
            if (auth()->check() && in_array('user_id', $model->getFillable())) {
                $model->user_id = auth()->id();
            }
        });
    }
}
