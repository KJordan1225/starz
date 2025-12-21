<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `orders`
            MODIFY COLUMN `status`
            ENUM(
                'active',
                'created',
                'requires_payment_method',
                'requires_action',
                'processing',
                'succeeded',
                'canceled',
                'failed',
				'NA'
            )
            NOT NULL
            DEFAULT 'created'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `orders`
            MODIFY COLUMN `status`
            ENUM('created','succeeded','failed','canceled')
            NOT NULL
            DEFAULT 'created'
        ");
    }
};
