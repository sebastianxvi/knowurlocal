<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('user_logs', function (Blueprint $table) {
        $table->unsignedBigInteger('agency_id')->nullable()->after('user_id');
    });
}

public function down()
{
    Schema::table('user_logs', function (Blueprint $table) {
        $table->dropColumn('agency_id');
    });
}
};
