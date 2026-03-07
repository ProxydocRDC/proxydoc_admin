<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('main_users', function (Blueprint $table) {
            if (! Schema::hasColumn('main_users', 'code_promo')) {
                $table->string('code_promo', 6)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('main_users', 'code_parrainage')) {
                $table->string('code_parrainage', 6)->nullable()->unique()->after('code_promo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_users', function (Blueprint $table) {
            $columns = array_values(array_filter(['code_promo', 'code_parrainage'], fn ($col) => Schema::hasColumn('main_users', $col)));
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
