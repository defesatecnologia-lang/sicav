<?php

require_once __DIR__ . '/../inc/navbar.php';
?>



<div class="container-fluid mt-3">
    <div class="row">
        <!-- Coluna da Webcam -->
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0">
                        <i class="fas fa-camera me-1"></i>WEBCAM
                    </h6>
                </div>
                <div class="card-body text-center p-3">

                    <div class="webcam-container mb-3">
                        <video id="webcamVideo" autoplay playsinline class="rounded border bg-light"></video>
                        <canvas id="webcamCanvas" class="d-none"></canvas>
                    </div>

                    <div class="d-grid gap-2">
                        <button id="captureBtn" class="btn btn-success btn-sm">
                            <i class="fas fa-camera me-1"></i> Capturar
                        </button>

                        <button id="retakeBtn" class="btn btn-warning btn-sm d-none">
                            <i class="fas fa-redo me-1"></i> Refazer
                        </button>

                        <button id="turnOffCamBtn" class="btn btn-secondary btn-sm">
                            <i class="fas fa-video-slash me-1"></i> Desligar Câmera
                        </button>

                        <button id="turnOnCamBtn" class="btn btn-primary btn-sm d-none">
                            <i class="fas fa-video me-1"></i> Ligar Câmera
                        </button>
                    </div>

                    <div id="photoPreview" class="mt-3"></div>

                </div>
            </div>
        </div>

        <!-- Coluna do Formulário -->
        <div class="col-lg-8 col-md-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-user-plus me-2"></i>CADASTRO DE VISITANTE
                        </h5>
                        <button type="button" class="btn btn-light btn-sm" id="btnReutilizarCadastro">
                            <i class="fas fa-recycle me-1"></i> Reutilizar Cadastro
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Indicador de Reutilização -->
                    <div id="reutilizacaoInfo" class="alert alert-info d-none">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Reutilizando cadastro:</strong>
                        <span id="infoCadastroOriginal"></span>
                        <button type="button" class="btn-close float-end" id="btnLimparReutilizacaoInfo"></button>
                    </div>
                    <!-- Formulário Principal -->
                    <form id="visitorForm" class="needs-validation" novalidate>
                        <input type="hidden" id="cadastro_original_id" name="cadastro_original_id" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="uname">Nome Completo *</label>
                                <input type="text" class="form-control border" id="uname" name="uname" required
                                    placeholder="Digite o nome completo">
                                <div class="invalid-feedback">Por favor, informe o nome completo.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="cpf">CPF *</label>
                                <input type="text" class="form-control border" id="cpf" name="cpf" required
                                    placeholder="000.000.000-00" maxlength="14">
                                <div class="invalid-feedback" id="cpfError">Por favor, informe um CPF válido.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="placa">Placa do Veículo</label>
                                <input type="text" class="form-control border text-uppercase" id="placa" name="placa"
                                    placeholder="AAA-0000 ou AAA-0A00" maxlength="8">
                                <div class="invalid-feedback" id="placaError">Placa inválida. Use AAA-0000 ou AAA-0A00.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="mobileno">Telefone</label>
                                <input type="tel" class="form-control border" id="mobileno" name="mobileno"
                                    placeholder="(00) 00000-0000" maxlength="15">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="email">E-mail</label>
                                <input type="email" class="form-control border" id="email" name="email"
                                    placeholder="email@exemplo.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="meet">Encontrar com *</label>
                                <input type="text" class="form-control border" id="meet" name="meet" required
                                    placeholder="Nome da pessoa ou departamento">
                                <div class="invalid-feedback">Por favor, informe com quem se encontrar.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="from">Empresa/Instituição</label>
                                <input type="text" class="form-control border" id="from" name="from"
                                    placeholder="Nome da empresa">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="representing">Tipo de Visita *</label>
                                <select class="form-select border" id="representing" name="representing" required>
                                    <option value="">Selecione o tipo...</option>
                                    <option value="Visita Técnica">Visita Técnica</option>
                                    <option value="Entrega">Entrega</option>
                                    <option value="Reunião">Reunião</option>
                                    <option value="Manutenção">Manutenção</option>
                                    <option value="Entrevista">Entrevista</option>
                                    <option value="Outros">Outros</option>
                                </select>
                                <div class="invalid-feedback">Por favor, selecione o tipo de visita.</div>
                            </div>
                        </div>
                        <!-- Botões de Ação -->
                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success btn-lg px-4">
                                <i class="fas fa-save me-1"></i> Cadastrar Visita
                            </button>
                            <button type="reset" class="btn btn-outline-secondary btn-lg px-4" id="btnLimparTudoCadastro">
                                <i class="fas fa-broom me-1"></i> Limpar Tudo
                            </button>
                            <a href="?page=home" class="btn btn-secondary btn-lg px-4">
                                <i class="fas fa-arrow-left me-1"></i> Voltar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reutilizar Cadastro -->
<div class="modal fade" id="reutilizarModal" tabindex="-1" aria-labelledby="reutilizarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="reutilizarModalLabel">
                    <i class="fas fa-recycle me-2"></i>Reutilizar Cadastro Anterior
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Busca -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info border-0">
                            <label class="form-label fw-bold" for="buscarCadastroModal">
                                <i class="fas fa-search me-1"></i>Buscar Cadastro Anterior
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control border-end-0" id="buscarCadastroModal"
                                    placeholder="Digite nome, CPF ou placa..." autocomplete="off">
                                <button class="btn btn-outline-info border-start-0" type="button" id="btnBuscarModal">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button class="btn btn-outline-secondary" type="button" id="btnLimparBuscaModal">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                Digite pelo menos 2 caracteres para buscar
                            </small>
                            <div id="statusBuscaModal" class="mt-2 small text-muted">
                                Digite para buscar cadastros anteriores
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Resultados -->
                <div class="row">
                    <div class="col-12">
                        <div class="border rounded p-3 u-scroll-max-400">
                            <h6 class="text-muted mb-3">Resultados da Busca:</h6>
                            <div id="resultadosBusca" class="row g-2">
                                <div class="col-12 text-center text-muted py-4">
                                    <i class="fas fa-search fa-2x mb-2 d-block"></i>
                                    Digite acima para buscar cadastros anteriores
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <small class="text-muted me-auto">
                    <i class="fas fa-info-circle me-1"></i>Será criado um novo registro mantendo o histórico
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal para o Crachá -->
<div class="modal fade" id="badgeModal" tabindex="-1" aria-labelledby="badgeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="badgeModalLabel">
                    <i class="fas fa-id-card me-2"></i> Crachá do Visitante
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="badgeModalContent">
                    <p class="text-muted">Os dados do crachá aparecerão aqui</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" id="btnImprimirCracha">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Incluir SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<!-- Scripts -->
<script src="<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/assets/js/main.js?v=<?= urlencode((string) @filemtime(__DIR__ . '/../public/assets/js/main.js')) ?>"></script>





<style nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

.form-control,
.form-select {
    border-radius: 8px;
}

.btn {
    border-radius: 8px;
}

.webcam-container {
    position: relative;
    border-radius: 8px;
    background: #f8f9fa;
    overflow: hidden;
}

#webcamVideo {
    width: 100%;
    height: 300px;
    object-fit: cover;
}

.cadastro-item {
    cursor: pointer;
    transition: all 0.2s ease;
}

.cadastro-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
}

.is-valid {
    border-color: #198754 !important;
}

.is-invalid {
    border-color: #dc3545 !important;
}

.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
}

#resultadosBusca {
    max-height: 300px;
    overflow-y: auto;
}

/* Melhorias de estilo */
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

/* Limitar largura máxima do formulário em telas grandes */
@media (min-width: 1200px) {
    .col-lg-8 .card {
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }
}
</style>



