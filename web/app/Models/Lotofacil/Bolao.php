<?php

namespace App\Models\Lotofacil;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

class Bolao extends Model
{
    use HasFactory;

    protected $table = 'lotofacil_boloes';

    protected $fillable = [
        'nome',
        'status',
        'concurso_alvo',
        'total_numeros',
        'max_moldura',
        'max_miolo',
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
        return $this->belongsToMany(User::class, 'lotofacil_bolao_user', 'lotofacil_bolao_id', 'user_id')
                    ->withPivot('numeros_selecionados');
    }

    public function fechamento(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Lotofacil\LotofacilFechamento::class, 'bolao_id');
    }

    public function isPendingForUser($userId): bool
    {
        $totalBolao = $this->total_numeros ?? 25;
        $pivotRecords = \Illuminate\Support\Facades\DB::table('lotofacil_bolao_user')
            ->where('lotofacil_bolao_id', $this->id)
            ->orderBy('id')
            ->get();
            
        $qtd = count($pivotRecords);
        if ($qtd == 0) return false;
        
        $porPessoa = (int)floor($totalBolao / $qtd);
        $sobra = (int)($totalBolao % $qtd);
        $ultimoUserId = $pivotRecords->last()->user_id;
        
        $myRecord = $pivotRecords->firstWhere('user_id', $userId);
        if (!$myRecord) return false;
        
        $limitePessoal = ($myRecord->user_id == $ultimoUserId) ? ($porPessoa + $sobra) : $porPessoa;
        $selecionadas = json_decode($myRecord->numeros_selecionados, true) ?? [];
        
        return count($selecionadas) < $limitePessoal;
    }
}
