<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UserSeeder::class);`
        // check user count
        if(\App\User::count() === 0)
            factory(App\User::class, 1)->create();
        factory(App\Kategori::class, 10)->create();
        factory(App\Produk::class, 10)->create();
        factory(App\Pelanggan::class, 10)->create();
        factory(App\Pesanan::class, 10)->create();
    }
}
