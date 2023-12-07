<?php

namespace Larabookir\Gateway\Database\Seeders\GatewaysTableSeeder;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class GatewaysTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $gateways = config('gateway');
        $gateways = Arr::except($gateways,["timezone", "table"]);
        foreach ($gateways as $gateway => $data)
        {
            $this->insertGateways($gateway, $data);
        }
    }

    private function insertGateways($gateway, $data)
    {
        $connectionInfo = $this->convertArraysToJson($data);
        DB::table('gateways')->insert([
            'gateway'        => $gateway,
            'merchant'       => Arr::get($data, 'merchant-id', Arr::get($data, 'terminalId', Arr::get($data, 'merchantId'))) ?? null,
            'callback_url'   => Arr::get($data, 'callback-url', '/'),
            'connection_info' => $connectionInfo,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    /**
     * Recursively converts arrays to JSON.
     *
     * @param mixed $data
     * @return mixed
     */
    private function convertArraysToJson($data)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                $value = $this->convertArraysToJson($value);
            }

            return json_encode($data);
        }

        return $data;
    }
}

