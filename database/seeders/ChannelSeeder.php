<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 设置大厅默认公告
        DB::table('channels')->where('name', 'lobby')->update([
            'announcement' => "欢迎来到未来道具研究所圆桌会议！\n\n【社区规则】\n1. 请尊重其他实验员，友好交流\n2. 禁止发布违规内容\n3. 这里是命运石之门的选择！\n\nEl Psy Kongroo.",
        ]);
    }
}
