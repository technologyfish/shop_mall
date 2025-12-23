<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    /**
     * 获取启用的公告列表
     */
    public function index()
    {
        $announcements = Announcement::getActive();
        return $this->success($announcements);
    }
}
