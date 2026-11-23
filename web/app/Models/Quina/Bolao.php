<?php

namespace App\Models\Quina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;

class Bolao extends Model
{
    use HasFactory;

    protected $table = 'quina_boloes';

    protected $fillable = [
        'nome',
        'status',
        'concurso_alvo',
        'total_numeros',
        'max_quadrante_1',
        'max_quadrante_2',
        'max_quadrante_3',
        'max_quadrante_4',
        'max_sub_quadrante',
        'max_linha',
        'max_coluna',
        'valor_cota',
        'valor_total',
        'dezenas_por_participante',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quina_bolao_user', 'quina_bolao_id', 'user_id')
                    ->withPivot('numeros_selecionados');
    }

    public function fechamento(): HasOne
    {
        return $this->hasOne(QuinaFechamento::class, 'bolao_id');
    }

    public function getLimiteDezenasForUser($userId): int
    {
        $totalBolao = $this->total_numeros ?? 5;
        $pivotRecords = \Illuminate\Support\Facades\DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->id)
            ->orderBy('id')
            ->get();
            
        $qtd = count($pivotRecords);
        if ($qtd === 0) return 0;
        
        $porPessoa = (int) floor($totalBolao / $qtd);
        $sobra = (int) ($totalBolao % $qtd);
        $ultimoUserId = $pivotRecords->last()->user_id;
        
        return ($userId == $ultimoUserId) ? ($porPessoa + $sobra) : $porPessoa;
    }

    public function isPendingForUser($userId): bool
    {
        if ($this->status >= 2) return false;

        $limitePessoal = $this->getLimiteDezenasForUser($userId);
        if ($limitePessoal === 0) return false;
        
        $myRecord = \Illuminate\Support\Facades\DB::table('quina_bolao_user')
            ->where('quina_bolao_id', $this->id)
            ->where('user_id', $userId)
            ->first();
            
        if (!$myRecord) return false;
        
        $selecionadas = json_decode($myRecord->numeros_selecionados, true) ?? [];
        return count($selecionadas) < $limitePessoal;
    }
}
