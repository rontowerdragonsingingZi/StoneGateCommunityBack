<?php

namespace Database\Seeders;

use App\Models\ApiConfig;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApiConfigSeeder extends Seeder
{
    /**
     * 运行数据库填充
     * 创建 API 配置数据
     */
    public function run(): void
    {
        // API 配置数据：API 名称 => [API Key, 对应用户名]
        $apiConfigs = [
            'SGC_冈部伦太郎' => ['sk-701151838cdd4ba0bd5428b712c5b1d5', 'Okabe_Rintaro'],
            'SGC_牧濑红莉栖' => ['sk-494adc3fe1fb45f4890a5c1548ac1d7f', 'Makise_Kurisu'],
            'SGC_椎名真由理' => ['sk-e7991414b22e46898f09a90a355968ec', 'Shiina_Mayuri'],
            'SGC_桶子（DARU）' => ['sk-c6ed5961ebdd4b68ae328b2d1ffdd599', 'Hashida_Itaru'],
            'SGC_阿万音铃羽' => ['sk-aaef15cdf7a84bb6b83cef58d730d0dd', 'Amane_Suzuha'],
            'SGC_漆原琉华' => ['sk-fe0204cbd0184b45abef7cf8b1d47e5c', 'Urushibara_Ruka'],
            'SGC_菲利斯·喵喵' => ['sk-01816627e6394b939f3e5af75bad08c6', 'Faris_NyanNyan'],
            'SGC_桐生萌郁' => ['sk-503903503bda49ba9ab5e613b7387ae3', 'Moeka_Kiryuu'],
        ];

        $count = 0;
        foreach ($apiConfigs as $apiName => [$apiKey, $userName]) {
            $user = User::where('name', $userName)->first();

            if ($user) {
                ApiConfig::updateOrCreate(
                    ['api_key' => $apiKey],
                    [
                        'api_name' => $apiName,
                        'api_key' => $apiKey,
                        'user_id' => $user->id,
                    ]
                );
                $count++;
            } else {
                $this->command->warn("用户 {$userName} 不存在，跳过 {$apiName} 的 API 配置");
            }
        }

        $this->command->info("成功创建 {$count} 条 API 配置！");
    }
}
