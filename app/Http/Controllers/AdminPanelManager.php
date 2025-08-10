<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LauncherImage;
use Illuminate\Http\JsonResponse;
use App\Services\AdminPanelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminPanelManager extends Controller
{
    private AdminPanelService $adminPanelService;

    public function __construct(AdminPanelService $adminPanelService)
    {
        $this->adminPanelService = $adminPanelService;
    }

    /**
     * Load the admin panel view
     *
     * @return View
     */
    public function adminPanelView()
    {
        return view('admin.admin_panel');
    }

    /**
     * Load the launcher images management view
     *
     * @return View
     */
    public function launcherImagesView()
    {
        return view('admin.launcher_images', [
            'launcherImages' => $this->adminPanelService->getLauncherImages(),
        ]);
    }

    /**
     * Handle the upload of a new launcher image
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function uploadNewLauncherImage(Request $request)
    {
        $request->validate([
            'launcher_file' => 'required|max:2048',
        ]);

        $in_prod = $request->has("in_prod");
        $file = $request->file('launcher_file');

        $this->adminPanelService->uploadNewLauncherImage($in_prod, $file);

        return back()->with('message', 'Success to upload: ' . $file->getClientOriginalName() . ' !');
    }

    /**
     * Update `in_prod` status of a launcher image
     *
     * @param LauncherImage $image
     * @return JsonResponse
     */
    public function toggleProdStatus(LauncherImage $image): JsonResponse
    {
        $image->in_prod = !$image->in_prod;
        $image->save();

        return response()->json([
            'message' => 'Success to update informations.',
            'in_prod' => $image->in_prod,
        ]);
    }
}
