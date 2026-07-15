<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الرحلة تحتاج أن "تتذكّر" كود الخصم المُطبَّق لحظة الحجز،
 * حتى يُطبَّق فعلياً عند إنشاء الدفعة بعد اكتمال الرحلة.
 * قبل هذا العمود كان الكود يُرسَل ثم يُتجاهَل تماماً (لا يُخصم ولا يُحتسب).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->foreignId('discount_code_id')
                ->nullable()
                ->after('car_type_id')
                ->constrained('discount_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropForeign(['discount_code_id']);
            $table->dropColumn('discount_code_id');
        });
    }
};
