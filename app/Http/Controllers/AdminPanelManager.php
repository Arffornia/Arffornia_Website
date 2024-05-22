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
            'launcher_version' => 'required',
            'file_upload' => 'required|mimes:jar|max:2048',
        ]);

        $version = $request->string("launcher_version");
        $in_prod = $request->has("in_prod");
        $file = $request->file('file_upload');

        $this->adminPanelService->uploadNewLauncherVersion($version, $in_prod, $file);

        return back()->with('message', 'Success to upload: ' . $file->getClientOriginalName() . ' !');;
    }

    public function launcherImagesView() {
        return view('admin.launcher_images');
    }

    public function uploadNewLauncherImage() {
    }
}
