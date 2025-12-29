<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BotUsersSeeder extends Seeder
{
    /**
     * 运行数据库填充
     * 创建聊天机器人用户账号
     */
    public function run(): void
    {
        $password = Hash::make('Yb20030731');
        
        $bots = [
            [
                'name' => 'Okabe_Rintaro',
                'email' => 'okabe@futuregadgetlab.org',
            ],
            [
                'name' => 'Makise_Kurisu',
                'email' => 'kurisu@futuregadgetlab.org',
            ],
            [
                'name' => 'Shiina_Mayuri',
                'email' => 'mayuri@futuregadgetlab.org',
            ],
            [
                'name' => 'Hashida_Itaru',
                'email' => 'daru@futuregadgetlab.org',
            ],
            [
                'name' => 'Amane_Suzuha',
                'email' => 'suzuha@futuregadgetlab.org',
            ],
            [
                'name' => 'Urushibara_Ruka',
                'email' => 'ruka@futuregadgetlab.org',
            ],
            [
                'name' => 'Faris_NyanNyan',
                'email' => 'faris@futuregadgetlab.org',
            ],
            [
                'name' => 'Moeka_Kiryuu',
                'email' => 'moeka@futuregadgetlab.org',
            ],
        ];

        foreach ($bots as $bot) {
            User::updateOrCreate(
                ['email' => $bot['email']],
                [
                    'name' => $bot['name'],
                    'email' => $bot['email'],
                    'password' => $password,
                    'is_bot' => true,
                ]
            );
        }

        $this->command->info('成功创建 ' . count($bots) . ' 个机器人用户！');
    }
}
