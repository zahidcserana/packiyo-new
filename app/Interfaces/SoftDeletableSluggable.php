<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface SoftDeletableSluggable extends Sluggable
{
    /**
     * @return boolean
     */
    public function trashed();
}
