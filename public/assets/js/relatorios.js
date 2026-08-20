

class RelatorioVisitantes {
  constructor() {
    this.tabela = null;
    this.dados = [];
    this.inicializado = false;

    this.init();
  }

  init() {
    if (this.inicializado) {
      return;
    }

    // Aguardar o DOM estar completamente pronto
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => {
        this.inicializarComponentes();
      });
    } else {
      this.inicializarComponentes();
    }
  }

  inicializarComponentes() {
    try {
      this.inicializarDatePickers();
      this.inicializarDataTable();
      this.configurarEventListeners();
      this.carregarEstatisticas();
      this.carregarRelatorio();
      this.protegerLinksNavbar();
      this.inicializado = true;
    } catch (error) {
      this.mostrarErro("Erro na inicialização do sistema: " + error.message);
    }
  }

  inicializarDatePickers() {
    const dataInicialElem = document.getElementById("dataInicial");
    const dataFinalElem = document.getElementById("dataFinal");

    if (!dataInicialElem || !dataFinalElem) {
      return;
    }

    // Verificar se flatpickr está disponível
    if (typeof flatpickr === "undefined") {
      this.definirDatasPadrao();
      return;
    }

    try {
      // Configurar flatpickr para data inicial
      flatpickr(dataInicialElem, {
        dateFormat: "d/m/Y",
        locale: "pt",
        allowInput: true,
        disableMobile: true,
        onChange: (selectedDates, dateStr) => {
        },
      });

      // Configurar flatpickr para data final
      flatpickr(dataFinalElem, {
        dateFormat: "d/m/Y",
        locale: "pt",
        allowInput: true,
        disableMobile: true,
        onChange: (selectedDates, dateStr) => {
        },
      });

      this.definirDatasPadrao();
    } catch (error) {
      this.definirDatasPadrao();
    }
  }

  definirDatasPadrao() {
    try {
      const dataFinal = new Date();
      const dataInicial = new Date();
      dataInicial.setDate(dataInicial.getDate() - 30); // Últimos 30 dias

      const dataInicialElem = document.getElementById("dataInicial");
      const dataFinalElem = document.getElementById("dataFinal");
      const horaInicialElem = document.getElementById("horaInicial");
      const horaFinalElem = document.getElementById("horaFinal");

      if (dataInicialElem)
        dataInicialElem.value = this.formatarData(dataInicial);
      if (dataFinalElem) dataFinalElem.value = this.formatarData(dataFinal);
      if (horaInicialElem) horaInicialElem.value = "00:00";
      if (horaFinalElem) horaFinalElem.value = "23:59";
    } catch (error) {
    }
  }

  inicializarDataTable() {
    // Verificar dependências
    if (typeof $ === "undefined") {
      this.mostrarErroTabela("jQuery não carregado. Recarregando...");
      setTimeout(() => this.inicializarDataTable(), 1000);
      return;
    }

    if (typeof $.fn.DataTable === "undefined") {
      this.mostrarErroTabela("DataTable não carregado. Recarregando...");
      setTimeout(() => this.inicializarDataTable(), 1000);
      return;
    }

    const tabelaElement = document.getElementById("tabelaRelatorios");
    if (!tabelaElement) {
      this.mostrarErroTabela("Elemento da tabela não encontrado");
      return;
    }

    try {
      this.tabela = $(tabelaElement).DataTable({
        language: {
          // url:"./assets/datatables/pt-BR.json",
          url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json",
        },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, "desc"]],
        responsive: true,
        processing: true,
        serverSide: false,
        searching: false,
        info: true,
        lengthChange: true,
        autoWidth: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        columns: [
          {
            data: "id",
            width: "70px",
            className: "dt-center",
          },
          {
            data: "nome_completo",
            render: $.fn.dataTable.render.text(),
            width: "200px",
          },
          {
            data: "numero_cpf",
            render: (data) => {
              if (!data) return "-";
              const cpf = data.toString().padStart(11, "0");
              return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
            },
            width: "140px",
          },
          {
            data: "veiculo_placa",
            render: (data) => {
              if (!data) return "-";
              return data.replace(/([A-Z]{3})([A-Z0-9]{4})/, "$1-$2");
            },
            width: "100px",
          },
          {
            data: "encontrar_com",
            render: $.fn.dataTable.render.text(),
            width: "150px",
          },
          {
            data: "empresa",
            render: $.fn.dataTable.render.text(),
            width: "150px",
          },
          {
            data: "tipo_visita",
            render: $.fn.dataTable.render.text(),
            width: "120px",
          },
          {
            data: "telefone",
            render: (data) => data || "-",
            width: "120px",
          },
          {
            data: "email",
            render: (data) => data || "-",
            width: "180px",
          },
          {
            data: "data_entrada",
            render: (data) => {
              if (!data) return "-";
              try {
                return new Date(data).toLocaleString("pt-BR");
              } catch (e) {
                return data;
              }
            },
            width: "150px",
          },
          {
            data: "data_saida",
            render: (data) => {
              if (!data) return "-";
              try {
                return new Date(data).toLocaleString("pt-BR");
              } catch (e) {
                return data;
              }
            },
            width: "150px",
          },
          {
            data: "status",
            render: (data) => {
              return data == 1
                ? '<span class="badge bg-success">Ativo</span>'
                : '<span class="badge bg-secondary">Finalizado</span>';
            },
            width: "100px",
            className: "dt-center",
          },
          {
            data: null,
            render: (data, type, row) => {
              if (type !== "display") return "";

              let botoes = `
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-info btn-sm" data-action="ver-detalhes" data-id="${row.id}" 
                                title="Ver detalhes" data-bs-toggle="tooltip">
                            <i class="fas fa-eye"></i>
                        </button>
                `;
              botoes += `</div>`;
              return botoes;
            },
            orderable: false,
            width: "100px",
            className: "dt-center",
          },
        ],
        initComplete: (settings, json) => {
          this.inicializarTooltips();
        },
        drawCallback: (settings) => {
          this.inicializarTooltips();
        },
        error: (settings, techNote, message) => {
          this.mostrarErroTabela(
            "Erro ao carregar dados da tabela: " + message
          );
        },
      });

    } catch (error) {
      this.mostrarErroTabela("Erro crítico na tabela: " + error.message);
    }
  }

  configurarEventListeners() {
    // Enter na pesquisa geral
    const pesquisaGeral = document.getElementById("pesquisaGeral");
    if (pesquisaGeral) {
      pesquisaGeral.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          this.carregarRelatorio();
        }
      });
    }

    // Change nos selects
    const filtroStatus = document.getElementById("filtroStatus");
    const filtroTipo = document.getElementById("filtroTipo");

    if (filtroStatus) {
      filtroStatus.addEventListener("change", () => this.carregarRelatorio());
    }
    if (filtroTipo) {
      filtroTipo.addEventListener("change", () => this.carregarRelatorio());
    }

    document
      .getElementById("btnFiltrarRelatorio")
      ?.addEventListener("click", () => this.carregarRelatorio());
    document
      .getElementById("btnLimparRelatorio")
      ?.addEventListener("click", () => this.limparFiltros());
    document
      .getElementById("btnHojeRelatorio")
      ?.addEventListener("click", () => this.filtrarHoje());
    document
      .getElementById("btnExportarExcelRelatorio")
      ?.addEventListener("click", () => this.exportarExcel());
    document
      .getElementById("btnExportarCsvRelatorio")
      ?.addEventListener("click", () => this.exportarCSV());
    document
      .getElementById("btnExportarPdfRelatorio")
      ?.addEventListener("click", () => this.exportarPDF());
    document
      .getElementById("btnImprimirRelatorio")
      ?.addEventListener("click", () => this.imprimirRelatorio());

    document.addEventListener("click", (event) => {
      const actionElement = event.target.closest("[data-action]");
      if (!actionElement) return;

      const action = actionElement.getAttribute("data-action");
      if (action === "ver-detalhes") {
        const id = actionElement.getAttribute("data-id");
        if (id) this.verDetalhes(id);
      } else if (action === "recarregar-relatorio") {
        this.carregarRelatorio();
      }
    });
  }
  // 🛡️ MÉTODO DE PROTEÇÃO DOS LINKS DA NAVBAR
  protegerLinksNavbar() {
    // Garantir que os links da navbar funcionem normalmente
    const navbarLinks = document.querySelectorAll(
      ".navbar a, .dropdown-menu a"
    );
    navbarLinks.forEach((link) => {
      // Clonar e substituir o link para remover event listeners conflitantes
      const newLink = link.cloneNode(true);
      link.parentNode.replaceChild(newLink, link);

      // Garantir que os links não tenham estilos que impeçam o clique
      newLink.style.pointerEvents = "auto";
      newLink.style.cursor = "pointer";
    });
  }

  inicializarTooltips() {
    if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
      const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
      );
      tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });
    }
  }

  async carregarEstatisticas() {
    try {
      const elementos = {
        total: document.getElementById("totalVisitantes"),
        ativos: document.getElementById("visitantesAtivos"),
        finalizados: document.getElementById("visitantesFinalizados"),
        hoje: document.getElementById("visitantesHoje"),
      };

      // Verificar se elementos existem
      for (const [key, element] of Object.entries(elementos)) {
        if (!element) {
          return;
        }
        // Mostrar loading
        element.textContent = "...";
      }

      const response = await fetch("?page=api/api_estatisticas");

      if (!response.ok) {
        throw new Error(`HTTP ${response.status} - ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        elementos.total.textContent = data.total || 0;
        elementos.ativos.textContent = data.ativos || 0;
        elementos.finalizados.textContent = data.finalizados || 0;
        elementos.hoje.textContent = data.hoje || 0;
      } else {
        throw new Error(data.error || "Erro desconhecido nas estatísticas");
      }
    } catch (error) {
      // Restaurar valores padrão em caso de erro
      const elementos = [
        "totalVisitantes",
        "visitantesAtivos",
        "visitantesFinalizados",
        "visitantesHoje",
      ];
      elementos.forEach((id) => {
        const elem = document.getElementById(id);
        if (elem) elem.textContent = "0";
      });
    }
  }

  // No método carregarRelatorio da classe RelatorioVisitantes
  async carregarRelatorio(forcarRecarregamento = false) {
    try {
      // Mostrar loading na tabela apenas se não for um carregamento em segundo plano
      if (!forcarRecarregamento) {
        this.mostrarLoadingTabela();
      }

      const filtros = this.obterFiltros();
      const queryString = new URLSearchParams(filtros).toString();

      const response = await fetch(`?page=api/api_relatorios&${queryString}`);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status} - ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        this.dados = data.dados || [];

        if (this.tabela) {
          this.tabela.clear().rows.add(this.dados).draw();

          if (this.dados.length === 0 && !forcarRecarregamento) {
          } else if (forcarRecarregamento) {
          }
        } else {
          this.mostrarDadosSemTabela(this.dados);
        }

        // Atualizar estatísticas após filtro (apenas se não for recarregamento em segundo plano)
        if (!forcarRecarregamento) {
          this.carregarEstatisticas();
        }
      } else {
        throw new Error(data.error || "Erro desconhecido na API de relatórios");
      }
    } catch (error) {
      if (!forcarRecarregamento) {
        this.mostrarErro("Erro ao carregar relatório: " + error.message);
        this.mostrarErroTabela("Erro ao carregar dados: " + error.message);
      }
    }
  }

  mostrarLoadingTabela() {
    const tbody = document.querySelector("#tabelaRelatorios tbody");
    if (tbody) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2 text-muted">Carregando dados...</p>
                    </td>
                </tr>
            `;
    }
  }

  mostrarErroTabela(mensagem) {
    const tbody = document.querySelector("#tabelaRelatorios tbody");
    if (tbody) {
      tbody.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>${mensagem}</p>
                        <button class="btn btn-sm btn-outline-primary" data-action="recarregar-relatorio">
                            <i class="fas fa-redo"></i> Tentar Novamente
                        </button>
                    </td>
                </tr>
            `;
    }
  }

  mostrarDadosSemTabela(dados) {
    const container = document.querySelector(".card-body");
    if (!container) return;

    let html = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                DataTable não carregado. Mostrando dados em formato simples.
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Placa</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

    const esc = (v) => String(v ?? "").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;");

    dados.forEach((item) => {
      html += `
                <tr>
                    <td>${esc(item.id)}</td>
                    <td>${esc(item.nome_completo || "-")}</td>
                    <td>${
                      item.numero_cpf
                        ? item.numero_cpf.replace(
                            /(\d{3})(\d{3})(\d{3})(\d{2})/,
                            "$1.$2.$3-$4"
                          )
                        : "-"
                    }</td>
                    <td>${
                      item.veiculo_placa
                        ? item.veiculo_placa.replace(
                            /([A-Z]{3})([A-Z0-9]{4})/,
                            "$1-$2"
                          )
                        : "-"
                    }</td>
                    <td>${
                      item.status == 1
                        ? '<span class="badge bg-success">Ativo</span>'
                        : '<span class="badge bg-secondary">Finalizado</span>'
                    }</td>
                    <td>
                        <button class="btn btn-sm btn-info" data-action="ver-detalhes" data-id="${esc(item.id)}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
    });

    html += `
                    </tbody>
                </table>
            </div>
        `;

    container.innerHTML += html;
  }

  obterFiltros() {
    const getValue = (id) => {
      const element = document.getElementById(id);
      return element ? element.value.trim() : "";
    };

    const filtros = {
      data_inicial: getValue("dataInicial"),
      data_final: getValue("dataFinal"),
      hora_inicial: getValue("horaInicial"),
      hora_final: getValue("horaFinal"),
      status: getValue("filtroStatus"),
      tipo_visita: getValue("filtroTipo"),
      pesquisa: getValue("pesquisaGeral"),
    };

    return filtros;
  }

  filtrarHoje() {

    try {
      const hoje = new Date();
      const dataHoje = this.formatarData(hoje);

      const dataInicialElem = document.getElementById("dataInicial");
      const dataFinalElem = document.getElementById("dataFinal");
      const horaInicialElem = document.getElementById("horaInicial");
      const horaFinalElem = document.getElementById("horaFinal");

      if (dataInicialElem) dataInicialElem.value = dataHoje;
      if (dataFinalElem) dataFinalElem.value = dataHoje;
      if (horaInicialElem) horaInicialElem.value = "00:00";
      if (horaFinalElem) horaFinalElem.value = "23:59";


      this.carregarRelatorio();
    } catch (error) {
      this.mostrarErro("Erro ao aplicar filtro: " + error.message);
    }
  }

  limparFiltros() {

    try {
      const form = document.getElementById("filtroForm");
      if (form) {
        form.reset();
      }

      // Resetar valores padrão
      const horaInicialElem = document.getElementById("horaInicial");
      const horaFinalElem = document.getElementById("horaFinal");

      if (horaInicialElem) horaInicialElem.value = "00:00";
      if (horaFinalElem) horaFinalElem.value = "23:59";

      // Reaplicar datas padrão
      this.definirDatasPadrao();


      // Recarregar relatório
      this.carregarRelatorio();
    } catch (error) {
      this.mostrarErro("Erro ao limpar filtros: " + error.message);
    }
  }

  async verDetalhes(id) {

    if (!id || isNaN(id)) {
      this.mostrarErro("ID inválido");
      return;
    }

    try {
      const response = await fetch(`?page=api/api_detalhes&id=${id}`);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status} - ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        this.mostrarModalDetalhes(data.dados);
      } else {
        throw new Error(data.error || "Erro ao carregar detalhes");
      }
    } catch (error) {
      this.mostrarErro("Erro ao carregar detalhes: " + error.message);
    }
  }

  mostrarModalDetalhes(dados) {

    if (!dados) {
      this.mostrarErro("Dados inválidos para exibição");
      return;
    }

    try {
      const conteudo = this.gerarConteudoDetalhes(dados);
      const detalhesConteudo = document.getElementById("detalhesConteudo");

      if (detalhesConteudo) {
        detalhesConteudo.innerHTML = conteudo;

        // Mostrar modal usando Bootstrap
        const modalElement = document.getElementById("detalhesModal");
        if (modalElement) {
          if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
          } else {
            // Fallback se Bootstrap não estiver disponível
            modalElement.style.display = "block";
            
          }
        }
      } else {
        throw new Error("Elemento #detalhesConteudo não encontrado");
      }
    } catch (error) {
      this.mostrarErro("Erro ao exibir detalhes: " + error.message);
    }
  }

  gerarConteudoDetalhes(dados) {
    const escapeHtml = (value) =>
      String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");

    const formatarCPF = (cpf) => {
      if (!cpf) return "Não informado";
      const cpfStr = cpf.toString().padStart(11, "0");
      return cpfStr.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    };

    const formatarPlaca = (placa) => {
      if (!placa) return "Não informado";
      return placa.replace(/([A-Z]{3})([A-Z0-9]{4})/, "$1-$2");
    };

    const formatarData = (data) => {
      if (!data) return "Não informado";
      try {
        return new Date(data).toLocaleString("pt-BR");
      } catch (e) {
        return data;
      }
    };
    const fotoHtml = dados.foto && dados.id
      ? `<img src="../api/get_photo.php?vid=${encodeURIComponent(dados.id)}"
               class="img-fluid rounded shadow-sm u-detalhe-foto">`
      : `<div class="bg-light rounded d-flex align-items-center justify-content-center shadow-sm u-detalhe-foto-placeholder">
          <i class="fas fa-user fa-3x text-muted"></i>
        </div>`;

    return `
            <div class="row">
                <div class="col-md-4 text-center mb-3">
                    ${fotoHtml}
                    <div class="mt-2">
                        <small class="text-muted">ID: ${
                          dados.id || "N/A"
                        }</small>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr><th width="35%">Nome Completo:</th><td>${
                                  escapeHtml(dados.nome_completo || "Não informado")
                                }</td></tr>
                                <tr><th>CPF:</th><td>${formatarCPF(
                                  dados.numero_cpf
                                )}</td></tr>
                                <tr><th>Placa do Veículo:</th><td>${formatarPlaca(
                                  dados.veiculo_placa
                                )}</td></tr>
                                <tr><th>Encontrar com:</th><td>${
                                  escapeHtml(dados.encontrar_com || "Não informado")
                                }</td></tr>
                                <tr><th>Empresa:</th><td>${
                                  escapeHtml(dados.empresa || "Não informado")
                                }</td></tr>
                                <tr><th>Tipo de Visita:</th><td>${
                                  escapeHtml(dados.tipo_visita || "Não informado")
                                }</td></tr>
                                <tr><th>Telefone:</th><td>${
                                  escapeHtml(dados.telefone || "Não informado")
                                }</td></tr>
                                <tr><th>E-mail:</th><td>${
                                  escapeHtml(dados.email || "Não informado")
                                }</td></tr>
                                <tr><th>Data de Entrada:</th><td>${formatarData(
                                  dados.data_entrada
                                )}</td></tr>
                                <tr><th>Data de Saída:</th><td>${formatarData(
                                  dados.data_saida
                                )}</td></tr>
                                <tr><th>Status:</th>
                                    <td>${
                                      dados.status == 1
                                        ? '<span class="badge bg-success">Ativo</span>'
                                        : '<span class="badge bg-secondary">Finalizado</span>'
                                    }</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
  }

  exportarExcel() {

    try {
      const filtros = this.obterFiltros();
      const queryString = new URLSearchParams({
        ...filtros,
        exportar: "excel",
      }).toString();

      window.open(`?page=api/api_export&${queryString}`, "_blank");
      this.mostrarMensagem("Exportação para Excel iniciada...", "info");
    } catch (error) {
      this.mostrarErro("Erro ao exportar para Excel: " + error.message);
    }
  }

  exportarCSV() {

    if (!this.dados || this.dados.length === 0) {
      this.mostrarErro("Não há dados para exportar");
      return;
    }

    try {
      const headers = [
        "ID",
        "Nome",
        "CPF",
        "Placa",
        "Encontrar com",
        "Empresa",
        "Tipo de Visita",
        "Telefone",
        "E-mail",
        "Data Entrada",
        "Data Saída",
        "Status",
      ];

      const escapeCsv = (value) => {
        const text = String(value ?? "");
        if (text.includes('"') || text.includes(";") || text.includes("\n")) {
          return `"${text.replace(/"/g, '""')}"`;
        }
        return text;
      };

      const formatarCPF = (cpf) =>
        cpf
          ? String(cpf).replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")
          : "";
      const formatarPlaca = (placa) =>
        placa
          ? String(placa).replace(/([A-Z]{3})([A-Z0-9]{4})/, "$1-$2")
          : "";
      const formatarDataHora = (data) =>
        data ? new Date(data).toLocaleString("pt-BR") : "";

      const linhas = this.dados.map((item) => [
        item.id,
        item.nome_completo || "",
        formatarCPF(item.numero_cpf),
        formatarPlaca(item.veiculo_placa),
        item.encontrar_com || "",
        item.empresa || "",
        item.tipo_visita || "",
        item.telefone || "",
        item.email || "",
        formatarDataHora(item.data_entrada),
        formatarDataHora(item.data_saida),
        item.status == 1 ? "Ativo" : "Finalizado",
      ]);

      const csv = [
        headers.map(escapeCsv).join(";"),
        ...linhas.map((linha) => linha.map(escapeCsv).join(";")),
      ].join("\n");

      const csvComBom = "\uFEFF" + csv;
      const blob = new Blob([csvComBom], {
        type: "text/csv;charset=utf-8;",
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `relatorios_visitantes_${
        new Date().toISOString().split("T")[0]
      }.csv`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);

      this.mostrarMensagem("CSV exportado com sucesso!", "success");
    } catch (error) {
      this.mostrarErro("Erro ao exportar CSV: " + error.message);
    }
  }

  exportarPDF() {

    if (!this.dados || this.dados.length === 0) {
      this.mostrarErro("Não há dados para exportar");
      return;
    }

    try {
      if (typeof jspdf === "undefined" || typeof jspdf.jsPDF === "undefined") {
        throw new Error("Biblioteca jsPDF não carregada");
      }

      const { jsPDF } = jspdf;
      const doc = new jsPDF();

      // Cabeçalho
      doc.setFontSize(16);
      doc.setTextColor(40, 40, 40);
      doc.text("RELATÓRIO DE VISITANTES", 105, 15, { align: "center" });

      doc.setFontSize(10);
      doc.setTextColor(100, 100, 100);
      doc.text(`Gerado em: ${new Date().toLocaleString("pt-BR")}`, 105, 22, {
        align: "center",
      });

      doc.text(`Total de registros: ${this.dados.length}`, 105, 28, {
        align: "center",
      });

      // Preparar dados da tabela
      const headers = [
        [
          "ID",
          "Nome",
          "CPF",
          "Placa",
          "Visitar",
          "Empresa",
          "Tipo",
          "Entrada",
          "Status",
        ],
      ];

      const data = this.dados.map((item) => [
        item.id.toString(),
        item.nome_completo || "",
        item.numero_cpf
          ? item.numero_cpf.replace(
              /(\d{3})(\d{3})(\d{3})(\d{2})/,
              "$1.$2.$3-$4"
            )
          : "-",
        item.veiculo_placa
          ? item.veiculo_placa.replace(/([A-Z]{3})([A-Z0-9]{4})/, "$1-$2")
          : "-",
        item.encontrar_com || "",
        item.empresa || "-",
        item.tipo_visita || "",
        item.data_entrada
          ? new Date(item.data_entrada).toLocaleDateString("pt-BR")
          : "-",
        item.status == 1 ? "Ativo" : "Finalizado",
      ]);

      // Gerar tabela
      doc.autoTable({
        head: headers,
        body: data,
        startY: 35,
        margin: { top: 35 },
        styles: {
          fontSize: 8,
          cellPadding: 2,
          lineColor: [200, 200, 200],
          lineWidth: 0.1,
        },
        headStyles: {
          fillColor: [52, 152, 219],
          textColor: 255,
          fontStyle: "bold",
        },
        alternateRowStyles: {
          fillColor: [245, 245, 245],
        },
        theme: "grid",
      });

      const fileName = `relatorios_visitantes_${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      doc.save(fileName);

      this.mostrarMensagem("PDF exportado com sucesso!", "success");
    } catch (error) {
      this.mostrarErro("Erro ao exportar PDF: " + error.message);
    }
  }

  imprimirRelatorio() {

    try {
      const tabelaElement = document.getElementById("tabelaRelatorios");
      if (!tabelaElement) {
        throw new Error("Tabela não encontrada");
      }

      // Clonar a tabela para impressão
      const tabelaClone = tabelaElement.cloneNode(true);

      // Remover coluna de ações da cópia
      $(tabelaClone).find("th:last, td:last").remove();

      // Criar janela de impressão
      const janela = window.open("", "_blank");
      janela.document.write(`
                <!DOCTYPE html>
                <html>
                    <head>
                        <title>Relatório de Visitantes</title>
                        <meta charset="UTF-8">
                        <style>
                            body { 
                                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                                margin: 20px; 
                                font-size: 12px;
                                color: #333;
                            }
                            .header { 
                                margin-bottom: 20px; 
                                padding-bottom: 15px;
                                border-bottom: 2px solid #007bff;
                            }
                            h2 { 
                                color: #007bff; 
                                margin: 0 0 5px 0;
                                font-size: 18px;
                            }
                            .info { 
                                color: #666; 
                                margin-bottom: 10px;
                            }
                            table { 
                                width: 100%; 
                                border-collapse: collapse; 
                                font-size: 10px;
                                margin-top: 10px;
                            }
                            th { 
                                background-color: #007bff !important; 
                                color: white !important;
                                font-weight: bold;
                                padding: 8px 6px;
                                border: 1px solid #ddd;
                            }
                            td { 
                                padding: 6px; 
                                border: 1px solid #ddd; 
                                vertical-align: top;
                            }
                            tr:nth-child(even) {
                                background-color: #f8f9fa;
                            }
                            .badge {
                                padding: 3px 6px;
                                border-radius: 3px;
                                font-size: 9px;
                                font-weight: bold;
                            }
                            .bg-success { background-color: #28a745 !important; }
                            .bg-secondary { background-color: #6c757d !important; }
                            @media print { 
                                body { margin: 0.5cm; }
                                .no-print { display: none; }
                            }
                            .footer {
                                margin-top: 20px;
                                padding-top: 10px;
                                border-top: 1px solid #ddd;
                                font-size: 9px;
                                color: #999;
                                text-align: center;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>Relatório de Visitantes</h2>
                            <div class="info">
                                <strong>Data do Relatório:</strong> ${new Date().toLocaleString(
                                  "pt-BR"
                                )}<br>
                                <strong>Total de Registros:</strong> ${
                                  this.dados.length
                                }
                            </div>
                        </div>
                        ${tabelaClone.outerHTML}
                        <div class="footer">
                            Relatório gerado pelo Sistema de Controle de Visitantes
                        </div>
                    </body>
                </html>
            `);
      janela.document.close();

      // Esperar o conteúdo carregar e imprimir
      setTimeout(() => {
        janela.focus();
        janela.print();
        // janela.close(); // Comente esta linha se quiser manter aberto para visualização
      }, 500);
    } catch (error) {
      this.mostrarErro("Erro ao preparar impressão: " + error.message);
    }
  }

  mostrarMensagem(mensagem, tipo = "info") {
    // Usar o sistema de toast do Bootstrap em vez de alertas customizados
    if (typeof showToast === "function") {
      showToast(mensagem, tipo);
    } else {
      // Fallback se showToast não estiver disponível
      const toast = document.createElement("div");
      toast.className = `alert alert-${tipo} alert-toast alert-dismissible fade show position-fixed`;
      toast.style.cssText =
        "top:20px;right:20px;z-index:9999;min-width:300px;max-width:500px;";
      toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${this.getIconeTipo(tipo)} me-2"></i>
                <div class="flex-grow-1">${mensagem}</div>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        `;
      document.body.appendChild(toast);

      // Auto-remover após 5 segundos
      setTimeout(() => {
        if (toast.parentNode) {
          toast.remove();
        }
      }, 5000);
    }
  }

  getIconeTipo(tipo) {
    const icones = {
      success: "fa-check-circle",
      danger: "fa-exclamation-triangle",
      warning: "fa-exclamation-circle",
      info: "fa-info-circle",
    };
    return icones[tipo] || "fa-info-circle";
  }

  mostrarErro(mensagem) {
    this.mostrarMensagem(mensagem, "danger");
  }

  formatarData(data) {
    if (!data) return "";

    try {
      const date = new Date(data);
      const dia = date.getDate().toString().padStart(2, "0");
      const mes = (date.getMonth() + 1).toString().padStart(2, "0");
      const ano = date.getFullYear();
      return `${dia}/${mes}/${ano}`;
    } catch (error) {
      return "";
    }
  }
  iniciarAtualizacaoAutomatica(intervalo = 60000) {

    
    setInterval(() => {
      if (document.visibilityState === "visible") {
        this.carregarEstatisticas();
        this.carregarRelatorio();
      }
    }, intervalo);
  }
}

// 🌐 INICIALIZAÇÃO GLOBAL

// Aguardar o DOM estar completamente pronto
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    window.relatorio = new RelatorioVisitantes();
  });
} else {
  window.relatorio = new RelatorioVisitantes();
}

// 🎯 FUNÇÕES GLOBAIS PARA USO NO HTML
function carregarRelatorio() {
  if (window.relatorio) {
    window.relatorio.carregarRelatorio();
  } else {
    alert("Sistema de relatórios não carregado. Recarregue a página.");
  }
}

function limparFiltros() {
  if (window.relatorio) {
    window.relatorio.limparFiltros();
  } else {
  }
}

function filtrarHoje() {
  if (window.relatorio) {
    window.relatorio.filtrarHoje();
  } else {
  }
}

function exportarExcel() {
  if (window.relatorio) {
    window.relatorio.exportarExcel();
  } else {
  }
}

function exportarCSV() {
  if (window.relatorio) {
    window.relatorio.exportarCSV();
  } else {
  }
}

function exportarPDF() {
  if (window.relatorio) {
    window.relatorio.exportarPDF();
  } else {
  }
}

function imprimirRelatorio() {
  if (window.relatorio) {
    window.relatorio.imprimirRelatorio();
  } else {
  }
}

// ✅ EXPORT PARA MÓDULOS (se necessário)
if (typeof module !== "undefined" && module.exports) {
  module.exports = RelatorioVisitantes;
}

