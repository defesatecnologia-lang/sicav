<?php
require_once __DIR__ . '/../inc/navbar.php';
?>

<style nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
body {
    margin: 0;
    padding: 0;

    background:
        linear-gradient(rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0.35)),
        url('<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/imagens/tela_fundo.png');

    background-size: 50%;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    height: 100vh;
}

@keyframes zoom {
    from {
        background-size: 30%;
    }

    to {
        background-size: 50%;
    }
}

.logo-fixa {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-60%);
    width: 260px;
    z-index: 9999;
    opacity: 0.95;
}
</style>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-list me-2"></i>Lista de Visitantes Ativos
                        </h4>
                        <button class="btn btn-light btn-sm py-1" id="btnAtualizarVisitantes"
                            title="Atualizar lista" data-bs-toggle="tooltip">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Lista de Visitantes Ativos -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-success">
                                <div
                                    class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-users me-2"></i>Visitantes Ativos no Local
                                    </h5>
                                    <span class="badge bg-light text-dark" id="contadorVisitantes">0</span>
                                </div>
                                <div class="card-body">
                                    <div id="listaVisitantesAtivos">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p>Carregando visitantes...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal de Confirmação de Saída -->
<div class="modal fade" id="modalConfirmacaoSaida" tabindex="-1" aria-labelledby="modalConfirmacaoSaidaLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalConfirmacaoSaidaLabel">
                    <i class="fas fa-door-open me-2"></i>Confirmar Saída
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-question-circle fa-3x text-warning"></i>
                </div>
                <h5 class="mb-3">Confirmar registro de saída?</h5>
                <p class="text-muted mb-1">Você está prestes a registrar a saída do visitante:</p>
                <p class="h6 text-primary mb-3" id="visitanteInfo"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-warning px-4" id="btnConfirmarSaida">
                    <i class="fas fa-check me-1"></i>Confirmar Saída
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Container para toasts -->
<div aria-live="polite" aria-atomic="true" class="position-relative">
    <div class="toast-container position-fixed top-0 end-0 p-3 u-zindex-9999"></div>
</div>
<script nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
let currentVisitorId = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function carregarListaVisitantes() {
    try {
        const container = document.getElementById("listaVisitantesAtivos");
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                <p>Carregando visitantes...</p>
            </div>
        `;
        const response = await fetch('?page=api/get_active_visitors');

        if (!response.ok) {
            throw new Error(`HTTP ${response.status} - ${response.statusText}`);
        }

        const visitantes = await response.json();

        if (!Array.isArray(visitantes)) {
            console.error("❌ Dados não são um array:", visitantes);
            throw new Error('Formato de dados inválido da API');
        }

        document.getElementById('contadorVisitantes').textContent = visitantes.length;

        if (visitantes.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-users-slash fa-2x mb-2"></i>
                    <p>Nenhum visitante ativo no momento</p>
                </div>
            `;
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th width="80">ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Placa</th>
                            <th>Visitar</th>
                            <th>Empresa</th>
                            <th>Entrada</th>
                            <th width="100" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        visitantes.forEach(visitante => {
            const id = visitante.id || 'N/A';
            const nome = escapeHtml(visitante.nome || visitante.nome_completo || 'Não informado');
            const cpf = escapeHtml(visitante.cpf || visitante.numero_cpf || '');
            const placa = escapeHtml(visitante.placa || visitante.veiculo_placa || '');
            const visitando = escapeHtml(visitante.visitando || visitante.encontrar_com || 'Não informado');
            const empresa = escapeHtml(visitante.empresa || 'Não informada');
            const dataEntrada = visitante.data_entrada || '';

            const dataFormatada = dataEntrada ?
                escapeHtml(new Date(dataEntrada).toLocaleString('pt-BR')) :
                'Não informada';

            const cpfFormatado = cpf ?
                cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4") :
                'Não informado';

            const placaFormatada = placa ?
                placa.replace(/([A-Z]{3})([A-Z0-9]{4})/, "$1-$2") :
                'Não informada';

            html += `
                <tr>
                    <td><span class="badge bg-secondary">${id}</span></td>
                    <td>${nome}</td>
                    <td>${cpfFormatado}</td>
                    <td>${placaFormatada}</td>
                    <td>${visitando}</td>
                    <td>${empresa}</td>
                    <td>${dataFormatada}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm py-1 px-2 btn-saida-visitante" 
                                data-id="${id}"
                                data-nome="${nome.replace(/"/g, "&quot;")}"
                                title="Registrar saída do visitante"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top">
                            <i class="fas fa-door-open me-1"></i>Saída
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <small class="text-muted">Total: ${visitantes.length} visitante(s) ativo(s)</small>
            </div>
        `;

        container.innerHTML = html;

        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        container.querySelectorAll('.btn-saida-visitante').forEach((btn) => {
            btn.addEventListener('click', () => {
                const visitanteId = btn.getAttribute('data-id');
                const visitanteNome = btn.getAttribute('data-nome') || '';
                abrirModalSaida(visitanteId, visitanteNome);
            });
        });

    } catch (error) {
        console.error('❌ Erro ao carregar visitantes:', error);
        document.getElementById('listaVisitantesAtivos').innerHTML = `
            <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Erro ao carregar lista</h5>
                        <p class="mb-0">${error.message}</p>
                        <small class="text-muted">Verifique o console para mais detalhes</small>
                    </div>
                </div>
            </div>
        `;
    }
}

function abrirModalSaida(visitorId, visitorName) {
    currentVisitorId = visitorId;
    document.getElementById('visitanteInfo').textContent = `ID: ${visitorId} - ${visitorName}`;
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmacaoSaida'));
    modal.show();
}
async function registrarSaida() {
    if (!currentVisitorId) return;

    try {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacaoSaida'));
        modal.hide();
        const btnConfirmar = document.getElementById('btnConfirmarSaida');
        const originalHTML = btnConfirmar.innerHTML;
        btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';
        btnConfirmar.disabled = true;

        const response = await fetch('?page=api/signout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            credentials: 'same-origin',
            body: JSON.stringify({ vid: currentVisitorId })
        });

        const resultado = await response.json();

        if (resultado.success) {
            showToast('✅ Saída registrada com sucesso!', 'success');
            setTimeout(() => {
                carregarListaVisitantes();
            }, 3000);

        } else if (response.status === 404 || response.status === 409) {
            showToast('❌ Visitante não encontrado ou já finalizado', 'danger');
        } else {
            showToast('❌ Erro ao registrar saída: ' + (resultado.message || 'erro'), 'danger');
        }
    } catch (error) {
        console.error('❌ Erro ao registrar saída:', error);
        showToast('❌ Erro de conexão: ' + error.message, 'danger');
    } finally {
        const btnConfirmar = document.getElementById('btnConfirmarSaida');
        btnConfirmar.innerHTML = '<i class="fas fa-check me-1"></i>Confirmar Saída';
        btnConfirmar.disabled = false;
        currentVisitorId = null;
    }
}

function showToast(mensagem, tipo = "info") {
    const safeMensagem = escapeHtml(mensagem);
    const toastContainer = document.querySelector('.toast-container');
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-bg-${tipo} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${safeMensagem}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 4000
    });

    toast.show();
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
document.addEventListener('DOMContentLoaded', function() {
    carregarListaVisitantes();

    document.getElementById('btnConfirmarSaida').addEventListener('click', registrarSaida);
    document.getElementById('btnAtualizarVisitantes')?.addEventListener('click', carregarListaVisitantes);

    setInterval(carregarListaVisitantes, 90000);

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>


