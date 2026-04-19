<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();
            $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_unique');
        });

        if (Schema::hasTable('product_tag')) {
            DB::table('product_tag')->orderBy('product_id')->chunk(500, function ($rows): void {
                $now = now();
                $payload = $rows->map(fn ($row) => [
                    'tag_id' => $row->tag_id,
                    'taggable_id' => $row->product_id,
                    'taggable_type' => Product::class,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($payload !== []) {
                    DB::table('taggables')->insertOrIgnore($payload);
                }
            });

            Schema::drop('product_tag');
        }
    }

    public function down(): void
    {
        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'tag_id']);
        });

        DB::table('taggables')
            ->where('taggable_type', Product::class)
            ->orderBy('id')
            ->chunk(500, function ($rows): void {
                $payload = $rows->map(fn ($row) => [
                    'product_id' => $row->taggable_id,
                    'tag_id' => $row->tag_id,
                ])->all();

                if ($payload !== []) {
                    DB::table('product_tag')->insertOrIgnore($payload);
                }
            });

        Schema::dropIfExists('taggables');
    }
};
