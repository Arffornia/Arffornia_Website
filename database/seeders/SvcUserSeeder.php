<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SvcUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $secret = env('SVC_ID', 'minecraft-server-svc');
        $svc_id = env('SVC_PASSWORD', 'minecraft-server-svc');

        User::updateOrCreate(
            ['name' => $svc_id],
            [
                'uuid' => Hash::make($secret),
                'role' => 'svc,team_editor,progression_editor,user_editor',
                'grade' => 'system',
                'money' => 0,
                'progress_point' => 0,
                'stage_id' => 1,
                'day_streak' => 0
            ]
        );
    }
}
