<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminPanelService;

class AdminPanelManager extends Controller
{
    private AdminPanelService $adminPanelService;

    public function __construct(AdminPanelService $adminPanelService)
    {
        $this->adminPanelService = $adminPanelService;
    }

    public function adminPanelView() {
        return view('admin.admin_panel');
    }
    public function launcherVersionsView() {
        return view('admin.launcher_versions');
    }
    public function uploadNewLauncherVersion(Request $request) {

        $request->validate([
            'file_upload' => 'required|mimes:jar|max:2048',
        ]);

        // Store the file in storage\app\public folder
        $file = $request->file('file_upload');
        $fileName = $file->getClientOriginalName();
        $fileName = str_replace(' ', '_', $fileName);
        $filePath = $file->storeAs('uploads/launcherVersions', $fileName, 'public');


    }
    public function launcherImagesView() {
        return view('admin.launcher_images');
    }
    public function uploadNewLauncherImage() {
    }
}
