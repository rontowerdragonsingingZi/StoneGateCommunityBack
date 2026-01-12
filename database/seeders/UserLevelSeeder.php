<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 初始化用户等级配置数据
     */
    public function run(): void
    {
        $levels = [
            [
                'level' => 0,
                'title' => '觀測者',
                'code' => 'Observer-β00',
                'icon' => '👁️',
                'quote' => '「在無數可能的世界線中，第一次覺醒的人。」',
                'description' => '初入命運之門的探索者，剛學會用終端觀察世界的變化。終端介面中仍閃爍著新手提示與系統錯誤訊息。',
                'min_experience' => 0,
                'color' => '#8b949e',
            ],
            [
                'level' => 1,
                'title' => '連線者',
                'code' => 'Linker-α03',
                'icon' => '🔗',
                'quote' => '「能夠感知世界線偏移的低頻信號。」',
                'description' => '開始能與他人心靈共振，理解命運的連鎖反應。在終端界面中，命令行回應時間縮短，數據流穩定。',
                'min_experience' => 500,
                'color' => '#58a6ff',
            ],
            [
                'level' => 2,
                'title' => '干涉者',
                'code' => 'Interference-Γ07',
                'icon' => '⚡',
                'quote' => '「敢於改寫既定命運的操作者。」',
                'description' => '掌握世界線干涉技術，能在終端輸入 rewrite.exe 改寫既定事件。偶爾會觸發「觀測者悖論」，導致記錄錯亂。',
                'min_experience' => 2000,
                'color' => '#d2a8ff',
            ],
            [
                'level' => 3,
                'title' => '偏移者',
                'code' => 'Shifter-Ω11',
                'icon' => '🌀',
                'quote' => '「能自由穿梭於不同世界線的存在。」',
                'description' => '命運不再是單線程運行的劇本，而是一張可重編譯的程式。終端開始顯示「DIVERGENCE METER」指數。',
                'min_experience' => 5000,
                'color' => '#f78166',
            ],
            [
                'level' => 4,
                'title' => '命運石之主',
                'code' => 'Steins;Gate Protocol-∞',
                'icon' => '🔮',
                'quote' => '「掌握觀測與干涉的終極平衡者。」',
                'description' => '已完全同步所有世界線的資訊流，在終端中輸入任何指令，皆可能改變歷史的根本邏輯。這是命運石之門的啟示者。',
                'min_experience' => 10000,
                'color' => '#ffd700',
            ],
        ];

        $now = now();
        foreach ($levels as &$level) {
            $level['created_at'] = $now;
            $level['updated_at'] = $now;
        }

        DB::table('user_levels')->insert($levels);
    }
}
