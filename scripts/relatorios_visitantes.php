<?php
require_once __DIR__ . '/../inc/navbar.php';
?>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Relatórios de Visitantes
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Estatísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5>Total</h5>
                                    <h2 id="totalVisitantes">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5>Ativos</h5>
                                    <h2 id="visitantesAtivos">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-secondary">
                                <div class="card-body">
                                    <h5>Finalizados</h5>
                                    <h2 id="visitantesFinalizados">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5>Hoje</h5>
                                    <h2 id="visitantesHoje">0</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Filtros</h5>
                        </div>
                        <div class="card-body">
                            <form id="filtroForm">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label for="dataInicial" class="form-label">Data Inicial</label>
                                        <input type="text" class="form-control datepicker" id="dataInicial"
                                            name="dataInicial">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="horaInicial" class="form-label">Hora Inicial</label>
                                        <input type="time" class="form-control" id="horaInicial" name="horaInicial"
                                            value="00:00">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="dataFinal" class="form-label">Data Final</label>
                                        <input type="text" class="form-control datepicker" id="dataFinal"
                                            name="dataFinal">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="horaFinal" class="form-label">Hora Final</label>
                                        <input type="time" class="form-control" id="horaFinal" name="horaFinal"
                                            value="23:59">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filtroStatus" class="form-label">Status</label>
                                        <select class="form-select" id="filtroStatus" name="filtroStatus">
                                            <option value="">Todos</option>
                                            <option value="1">Ativos</option>
                                            <option value="0">Finalizados</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="filtroTipo" class="form-label">Tipo Visita</label>
                                        <select class="form-select" id="filtroTipo" name="filtroTipo">
                                            <option value="">Todos</option>
                                            <option value="Visita Técnica">Visita Técnica</option>
                                            <option value="Entrega">Entrega</option>
                                            <option value="Reunião">Reunião</option>
                                            <option value="Manutenção">Manutenção</option>
                                            <option value="Entrevista">Entrevista</option>
                                            <option value="Outros">Outros</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="pesquisaGeral" class="form-label">Pesquisa Geral</label>
                                        <input type="text" class="form-control" id="pesquisaGeral"
                                            placeholder="Nome, CPF, placa, empresa...">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label" aria-hidden="true">&nbsp;</div>
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="button" class="btn btn-primary me-md-2" id="btnFiltrarRelatorio">
                                                <i class="fas fa-search me-1"></i>Filtrar
                                            </button>
                                            <button type="button" class="btn btn-secondary me-md-2" id="btnLimparRelatorio">
                                                <i class="fas fa-times me-1"></i>Limpar
                                            </button>
                                            <button type="button" class="btn btn-outline-primary me-md-2" id="btnHojeRelatorio">
                                                <i class="fas fa-calendar-day me-1"></i>Hoje
                                            </button>
                                            <button type="button" class="btn btn-success me-md-2" id="btnExportarExcelRelatorio">
                                                <i class="fas fa-file-excel me-1"></i>Excel
                                            </button>
                                            <button type="button" class="btn btn-outline-success me-md-2" id="btnExportarCsvRelatorio">
                                                <i class="fas fa-file-csv me-1"></i>CSV
                                            </button>
                                            <button type="button" class="btn btn-danger me-md-2" id="btnExportarPdfRelatorio">
                                                <i class="fas fa-file-pdf me-1"></i>PDF
                                            </button>
                                            <button type="button" class="btn btn-info" id="btnImprimirRelatorio">
                                                <i class="fas fa-print me-1"></i>Imprimir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Tabela -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Resultados</h5>
                        </div>
                        <div class="card-body">
                            <table id="tabelaRelatorios" class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>CPF</th>
                                        <th>Placa</th>
                                        <th>Encontrar com</th>
                                        <th>Empresa</th>
                                        <th>Tipo Visita</th>
                                        <th>Telefone</th>
                                        <th>E-mail</th>
                                        <th>Data Entrada</th>
                                        <th>Data Saída</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detalhes do Visitante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalhesConteudo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<!-- Container para toasts -->
<div aria-live="polite" aria-atomic="true" class="position-relative">
    <div class="toast-container position-fixed top-0 end-0 p-3 u-zindex-9999"></div>
</div>

<!-- Scripts adicionais (jQuery e Bootstrap já carregados no header) -->

<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<!-- PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<!-- Nosso script -->
<script src="<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/assets/js/relatorios.js"></script>


