<?php

namespace App\Models\Core\Content;

use App\Policies\FaqPolicy;
use Database\Factories\Core\Content\FaqCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(FaqPolicy::class)]
class FaqCategory extends Model
{
    /** @use HasFactory<FaqCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<FaqItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
