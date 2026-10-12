<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->json('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('portfolio_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug', 100)->unique();
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('body')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->json('featured_image_alt')->nullable();
            $table->json('gallery')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('client')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['status', 'sort_order', 'id']);
            $table->timestamps();
        });
        Schema::table('portfolio_pages', function (Blueprint $table): void {
            foreach (['listing_title', 'eyebrow', 'introduction', 'callout_title', 'callout_content', 'related_title', 'related_eyebrow', 'whatsapp_label'] as $field) {
                $table->json($field)->nullable();
            }
            $table->string('whatsapp_url', 2048)->default('https://wa.me/34694229352');
        });
        DB::table('portfolio_pages')->update([
            'listing_title' => json_encode(['ca' => 'Projectes', 'es' => 'Proyectos']),
            'eyebrow' => json_encode(['ca' => 'Arxius · Recerca · Memòria', 'es' => 'Archivos · Investigación · Memoria']),
            'introduction' => json_encode(['ca' => 'Històries que mereixen ser conservades. Una mirada a la manera d’ordenar, descobrir i compartir el nostre patrimoni documental.', 'es' => 'Historias que merecen ser conservadas. Una mirada a la manera de ordenar, descubrir y compartir nuestro patrimonio documental.']),
            'callout_title' => json_encode(['ca' => 'Parlem del teu projecte', 'es' => 'Hablemos de tu proyecto']),
            'callout_content' => json_encode(['ca' => 'Un arxiu per ordenar, una història per descobrir. Comencem amb una conversa.', 'es' => 'Un archivo por ordenar, una historia por descubrir. Empecemos con una conversación.']),
            'related_title' => json_encode(['ca' => 'Altres projectes', 'es' => 'Otros proyectos']),
            'related_eyebrow' => json_encode(['ca' => 'Continua descobrint', 'es' => 'Sigue descubriendo']),
            'whatsapp_label' => json_encode(['ca' => 'Contactar per WhatsApp', 'es' => 'Contactar por WhatsApp']),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_projects');
        Schema::dropIfExists('portfolio_categories');
        Schema::table('portfolio_pages', function (Blueprint $table): void {
            $table->dropColumn(['listing_title', 'eyebrow', 'introduction', 'callout_title', 'callout_content', 'related_title', 'related_eyebrow', 'whatsapp_label', 'whatsapp_url']);
        });
    }
};
