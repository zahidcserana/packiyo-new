<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDuplicateToTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tags', function (Blueprint $table) {
            $tags = Tag::withTrashed()->get()->map->only(['name', 'id']);
            $tagList = [];

            foreach ($tags as $tag) {
                $tagList[$tag['name']][] = $tag['id'];
            }

            $i = 1;

            foreach ($tagList as $key => $tagIds) {
                echo $i++ . '/' . count($tagList) . ' ' . $key . ' ' . count($tagIds) . "\n";

                $tagIdFirst = $tagIds[0];
                array_shift($tagIds);

                foreach ($tagIds as $tagId) {
                    $taggables = DB::table('taggables')->where('tag_id', $tagId)->get();

                    foreach ($taggables as $taggable) {
                        if ($taggable) {
                            $modelFQN = $taggable->taggable_type;
                            $model = $modelFQN::find($taggable->taggable_id);

                            if ($model) {
                                $model->tags()->detach($tagId);

                                if ($model->tags()->where('tag_id', $tagIdFirst)->doesntExist()) {
                                    $model->tags()->attach($tagIdFirst);
                                }
                            } else {
                                DB::table('taggables')
                                    ->where('tag_id', $tagId)
                                    ->where('taggable_id', $taggable->taggable_id)
                                    ->where('taggable_type', $taggable->taggable_type)
                                    ->delete();
                            }
                        }
                    }
                }

                Tag::whereIn('id', $tagIds)->forceDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tags', function (Blueprint $table) {
            //
        });
    }
}
