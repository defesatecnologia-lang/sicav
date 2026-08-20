// ✅ VARIÁVEIS GLOBAIS
let currentVisitorId = null;

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&")
    .replace(/</g, "<")
    .replace(/>/g, ">")
    .replace(/"/g, """)
    .replace(/'/g, "'");
}

// ✅ HELPER PARA LOG CONDICIONAL (não loga em produção)
function devLog(...args) {
  if (!IS_PRODUCTION) console.log(...args);
}

function devError(...args) {
  if (!IS_PRODUCTION) console.error(...args);
}

function devWarn(...args) {
  if (!IS_PRODUCTION) console.warn(...args);
}

let isVisitorRegistered = false;
let currentVisitorData = null;
let currentReusedId = null;

function showToast(message, type = "info") {
  // Criar um toast simples do Bootstrap
  const toastContainer =
    document.getElementById("toastContainer") || createToastContainer();

  const toastEl = document.createElement("div");
  toastEl.className = `toast align-items-center text-bg-${
    type === "error" ? "danger" : type === "warning" ? "warning" : "success"
  } border-0`;
  toastEl.setAttribute("role", "alert");
  toastEl.setAttribute("aria-live", "assertive");
  toastEl.setAttribute("aria-atomic", "true");

  const bodyDiv = document.createElement('div');
  bodyDiv.className = 'toast-body';
  bodyDiv.textContent = message;

  const dFlex = document.createElement('div');
  dFlex.className = 'd-flex';
  dFlex.appendChild(bodyDiv);

  const closeBtn = document.createElement('button');
  closeBtn.type = 'button';
  closeBtn.className = 'btn-close btn-close-white me-2 m-auto';
  closeBtn.setAttribute('data-bs-dismiss', 'toast');

  dFlex.appendChild(closeBtn);
  toastEl.appendChild(dFlex);

  toastContainer.appendChild(toastEl);

  const toast = new bootstrap.Toast(toastEl, {
    autohide: true,
    delay: 4000,
  });

  toast.show();
  toastEl.addEventListener("hidden.bs.toast", () => {
    toastEl.remove();
  });
}

function createToastContainer() {
  const container = document.createElement("div");
  container.id = "toastContainer";
  container.className = "toast-container position-fixed top-0 end-0 p-3";
  container.style.zIndex = "9999";
  document.body.appendChild(container);
  return container;
}

function showErrorModal(title, message, type = "error") {
  return Swal.fire({
    title: type === "error" ? "❌ " + title : "⚠️ " + title,
    html: message,
    icon: type,
    confirmButtonText: "Entendi",
    confirmButtonColor: type === "error" ? "#d33" : "#f0ad4e",
    allowOutsideClick: true, // ✅ PERMITIR FECHAR CLICANDO FORA
    allowEscapeKey: true, // ✅ PERMITIR FECHAR COM ESC
    allowEnterKey: true, // ✅ PERMITIR FECHAR COM ENTER
    focusConfirm: true, // ✅ FOCAR NO BOTÃO DE CONFIRMAÇÃO
    buttonsStyling: true,
    showClass: {
      popup: "animate__animated animate__fadeInDown",
    },
    hideClass: {
      popup: "animate__animated animate__fadeOutUp",
    },
  });
}

function showActiveVisitorModal(
  visitorName,
  visitorId,
  conflictType,
  conflictValue
) {
  const conflictText =
    conflictType === "cpf"
      ? `CPF: <strong>${formatCPF(conflictValue)}</strong>`
      : `Placa: <strong>${conflictValue}</strong>`;

  const message = `
        <div class="conflict-modal">
            <div class="alert-header">
                <div class="alert-icon">🚫</div>
                <h3>Visita Ativa Encontrada</h3>
            </div>
            
            <div class="conflict-details">
                <div class="detail-item">
                    <span class="label">Visitante:</span>
                    <span class="value">${visitorName}</span>
                </div>
                <div class="detail-item">
                    <span class="label">ID do Registro:</span>
                    <span class="value badge">#${visitorId}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Conflito em:</span>
                    <span class="value conflict-value">${conflictText}</span>
                </div>
            </div>

            <div class="alert-message">
                <p>⚠️ <strong>Não é possível registrar uma nova visita</strong></p>
                <p>Este visitante já possui uma visita em andamento. Finalize a visita atual antes de criar uma nova.</p>
            </div>

            <div class="suggestions">
                <h4>O que fazer?</h4>
                <ul>
                    <li>✅ Verifique se o visitante já fez o checkout</li>
                    <li>✅ Finalize a visita atual no sistema</li>
                    <li>✅ Entre em contato com a portaria</li>
                </ul>
            </div>
        </div>
    `;

  return Swal.fire({
    title: "Visita Duplicada",
    html: message,
    icon: "warning",
    confirmButtonText: "Entendi, vou verificar",
    confirmButtonColor: "#f0ad4e",
    showCancelButton: true,
    cancelButtonText: "Ver Registro",
    cancelButtonColor: "#3085d6",
    width: "600px",
    allowOutsideClick: true, // ✅ PERMITIR FECHAR CLICANDO FORA
    allowEscapeKey: true, // ✅ PERMITIR FECHAR COM ESC
    allowEnterKey: true, // ✅ PERMITIR FECHAR COM ENTER
    focusConfirm: false, // ✅ NÃO FOCAR AUTOMATICAMENTE
    buttonsStyling: true,
    showClass: {
      popup: "animate__animated animate__fadeInDown",
    },
    hideClass: {
      popup: "animate__animated animate__fadeOutUp",
    },
  }).then((result) => {
    if (result.dismiss === Swal.DismissReason.cancel) {
      window.open(`?page=listar_visitantes`, "_blank");
    }
    // ✅ SEMPRE RETORNAR A PROMESSA
    return result;
  });
}

function showSuccessModal(title, message) {
  return Swal.fire({
    title: "✅ " + title,
    html: message,
    icon: "success",
    confirmButtonText: "Ótimo!",
    confirmButtonColor: "#28a745",
    allowOutsideClick: true, // ✅ PERMITIR FECHAR CLICANDO FORA
    allowEscapeKey: true, // ✅ PERMITIR FECHAR COM ESC
    allowEnterKey: true, // ✅ PERMITIR FECHAR COM ENTER
    focusConfirm: true, // ✅ FOCAR NO BOTÃO DE CONFIRMAÇÃO
    buttonsStyling: true,
    showClass: {
      popup: "animate__animated animate__bounceIn",
    },
    hideClass: {
      popup: "animate__animated animate__fadeOutUp",
    },
  });
}

function sanitizeHtml(html) {
  const temp = document.createElement("div");
  temp.innerHTML = html;
  temp.querySelectorAll("script, style, iframe, object, embed").forEach(el => el.remove());
  return temp.innerHTML;
}

// ✅ FUNÇÃO GLOBAL PARA IMPRIMIR CRACHÁ
function imprimirCracha() {
  const badgeElement = document.getElementById("badgeModalContent");
  const conteudoCracha = sanitizeHtml(badgeElement.innerHTML);

  const janelaImpressao = window.open("", "_blank", "width=400,height=600");

  janelaImpressao.document.write(`
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Imprimir Crachá</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body { 
                    margin: 0; 
                    padding: 20px;
                    background: white !important;
                    font-family: Arial, sans-serif;
                }
                .cracha-container { 
                    border: 2px solid #007bff !important;
                    border-radius: 10px;
                    padding: 15px;
                }
                @media print {
                    body { 
                        padding: 10px;
                        margin: 0;
                    }
                    .cracha-container { 
                        border: 2px solid #000 !important;
                        margin: 0 auto;
                        box-shadow: none;
                    }
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="cracha-container">
                ${conteudoCracha}
            </div>
        </body>
        </html>
    `);

  janelaImpressao.document.close();
  janelaImpressao.onload = function () {
    janelaImpressao.print();
    setTimeout(function () {
      janelaImpressao.close();
    }, 1000);
  };
}

// Função auxiliar para formatar CPF
function formatCPF(cpf) {
  if (!cpf) return "";
  cpf = cpf.replace(/\D/g, "");
  return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
}

// ✅ FUNÇÃO PARA LIMPAR REUTILIZAÇÃO
function limparReutilizacao() {
  currentReusedId = null;
  const infoDiv = document.getElementById("reutilizacaoInfo");
  if (infoDiv) {
    infoDiv.classList.add("d-none");
  }

  const originalIdInput = document.getElementById("cadastro_original_id");
  if (originalIdInput) {
    originalIdInput.value = "";
  }
}

function configurarIntegracaoReutilizacao() {
  window.limparReutilizacao = limparReutilizacao;

  window.selecionarCadastro = function (card) {
    const cadastro = {
      id: card.getAttribute("data-id"),
      nome: card.getAttribute("data-nome"),
      cpf: card.getAttribute("data-cpf"),
      placa: card.getAttribute("data-placa"),
      telefone: card.getAttribute("data-telefone"),
      email: card.getAttribute("data-email"),
      empresa: card.getAttribute("data-empresa"),
      foto: card.getAttribute("data-foto"), // ✅ ADICIONADO: campo foto
    };

    devLog("✅ Selecionando cadastro para reutilização:", cadastro);

    // Preencher formulário
    document.getElementById("uname").value =
      cadastro.nome !== "N/A" ? cadastro.nome : "";
    document.getElementById("cpf").value =
      cadastro.cpf !== "N/A" ? cadastro.cpf : "";
    document.getElementById("placa").value =
      cadastro.placa !== "N/A" ? cadastro.placa : "";
    validarPlaca(document.getElementById("placa"));
    document.getElementById("mobileno").value =
      cadastro.telefone !== "N/A" ? cadastro.telefone : "";
    document.getElementById("email").value =
      cadastro.email !== "N/A" ? cadastro.email : "";
    document.getElementById("from").value =
      cadastro.empresa !== "N/A" ? cadastro.empresa : "";

    currentReusedId = cadastro.id;

    const originalIdInput = document.getElementById("cadastro_original_id");
    if (originalIdInput) {
      originalIdInput.value = cadastro.id;
    }

    // Carregar foto do cadastro anterior via endpoint protegido
    if (cadastro.foto && cadastro.foto !== "N/A" && cadastro.foto !== "" && cadastro.id) {
      devLog("🖼️ Carregando foto do cadastro anterior:", cadastro.foto);

      // Converter a imagem do servidor para base64
      fetch(`../api/get_photo.php?vid=${encodeURIComponent(cadastro.id)}`)
        .then((response) => {
          if (!response.ok) {
            throw new Error(`Erro ao carregar foto: ${response.status}`);
          }
          return response.blob();
        })
        .then((blob) => {
          const reader = new FileReader();
          reader.onloadend = () => {
            // ✅ DEFINIR A FOTO NO SISTEMA
            if (window.visitorSystem) {
              window.visitorSystem.photoData = reader.result;
              window.visitorSystem.showPhotoPreview();
              showToast("Foto do cadastro anterior carregada!", "success");
            } else {
              devError("❌ visitorSystem não encontrado");
            }
          };
          reader.readAsDataURL(blob);
        })
        .catch((error) => {
          devError("❌ Erro ao carregar foto:", error);
          showToast(
            "Erro ao carregar foto anterior - Capture uma nova foto",
            "warning"
          );
        });
    } else {
      devLog("ℹ️ Nenhuma foto encontrada no cadastro anterior");
      showToast("Cadastro carregado - Capture uma nova foto", "info");
    }

    // Mostrar indicador
    const infoDiv = document.getElementById("reutilizacaoInfo");
    const infoSpan = document.getElementById("infoCadastroOriginal");

    if (infoDiv && infoSpan) {
      infoSpan.textContent = `${cadastro.nome} (CPF: ${cadastro.cpf}) - ID Original: ${cadastro.id}`;
      infoDiv.classList.remove("d-none");
    }

    // Fechar modal
    const modalElement = document.getElementById("reutilizarModal");
    if (modalElement) {
      const modal = bootstrap.Modal.getInstance(modalElement);
      if (modal) modal.hide();
    }

    // Focar no próximo campo obrigatório
    setTimeout(() => {
      document.getElementById("meet").focus();
    }, 300);
  };
}

function validarCPF(campo) {
  const cpf = campo.value.replace(/\D/g, "");
  const cpfError = document.getElementById("cpfError");

  if (cpf.length === 0) {
    campo.classList.remove("is-valid", "is-invalid");
    cpfError.textContent = "Por favor, informe um CPF válido.";
    return false;
  }

  if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    cpfError.textContent = "CPF inválido.";
    return false;
  }

  // Cálculo de validação do CPF
  let soma = 0;
  let resto;

  for (let i = 1; i <= 9; i++) {
    soma = soma + parseInt(cpf.substring(i - 1, i)) * (11 - i);
  }

  resto = (soma * 10) % 11;
  if (resto === 10 || resto === 11) resto = 0;
  if (resto !== parseInt(cpf.substring(9, 10))) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    cpfError.textContent = "CPF inválido.";
    return false;
  }

  soma = 0;
  for (let i = 1; i <= 10; i++) {
    soma = soma + parseInt(cpf.substring(i - 1, i)) * (12 - i);
  }

  resto = (soma * 10) % 11;
  if (resto === 10 || resto === 11) resto = 0;
  if (resto !== parseInt(cpf.substring(10, 11))) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    cpfError.textContent = "CPF inválido.";
    return false;
  }

  campo.classList.remove("is-invalid");
  campo.classList.add("is-valid");
  return true;
}

function validarPlaca(campo) {
  if (!campo) return true;
  const placaError = document.getElementById("placaError");
  const valor = (campo?.value || "").toUpperCase().trim();

  if (!valor) {
    campo.classList.remove("is-valid", "is-invalid");
    if (placaError) {
      placaError.textContent = "Placa inválida. Use AAA-0000 ou AAA-0A00.";
    }
    return true;
  }

  const placaLimpa = valor.replace(/-/g, "");
  const padraoAntigo = /^[A-Z]{3}[0-9]{4}$/;
  const padraoMercosul = /^[A-Z]{3}[0-9][A-Z][0-9]{2}$/;
  const valida = padraoAntigo.test(placaLimpa) || padraoMercosul.test(placaLimpa);

  if (!valida) {
    campo.classList.remove("is-valid");
    campo.classList.add("is-invalid");
    if (placaError) {
      placaError.textContent = "Placa inválida. Use AAA-0000 ou AAA-0A00.";
    }
    return false;
  }

  campo.classList.remove("is-invalid");
  campo.classList.add("is-valid");
  return true;
}

class VisitorSystem {
  constructor() {
    this.stream = null;
    this.photoData = null;
    this.isProcessing = false;
    this.pendingClearAfterModal = false;
    this.currentModal = null;
    this.reuseModal = null;
    this.searchTimeout = null;
    this.init();
  }

  async init() {
    devLog("🚀 Iniciando VisitorSystem...");
    try {
      await this.initWebcam();
      this.setupEventListeners();
      this.configurarMascaras();
      this.configurarCamposMaiusculos();
      configurarIntegracaoReutilizacao();
      this.setupReutilizacaoModal();

      devLog("✅ Sistema carregado com sucesso!");
    } catch (error) {
      devError("Erro na inicialização:", error);
      devLog("Erro ao carregar sistema:", error.message);
    }
  }

  setDefaultPhoto() {
    // Placeholder visual apenas para a UI; nao enviar para API.
    this.photoData = null;
    const photoPreview = document.getElementById("photoPreview");
    if (photoPreview) {
      photoPreview.innerHTML = `
      <div class="text-center">
        <div class="bg-light rounded d-flex align-items-center justify-content-center mx-auto" style="width:150px;height:150px;">
          <i class="fas fa-user text-muted fa-3x"></i>
        </div>
        <div class="mt-2 text-muted">Sem foto (webcam indisponivel)</div>
      </div>
    `;
    }
    showToast("Foto padrão definida", "info");
  }

  async initWebcam() {
    try {
      const video = document.getElementById("webcamVideo");
      if (!video) {
        this.updateCameraButtons(false);
        return;
      }

      this.stream = await navigator.mediaDevices.getUserMedia({
        video: { width: 640, height: 480 },
      });
      video.srcObject = this.stream;
      this.updateCameraButtons(true);
    } catch (err) {
      devError("Erro webcam:", err);
      this.updateCameraButtons(false);
      showToast("Webcam não acessível - usando foto padrão", "warning");
      this.setDefaultPhoto();
    }
  }

  setupEventListeners() {
    const captureBtn = document.getElementById("captureBtn");
    const retakeBtn = document.getElementById("retakeBtn");
    const turnOffCamBtn = document.getElementById("turnOffCamBtn");
    const turnOnCamBtn = document.getElementById("turnOnCamBtn");
    const btnImprimirCracha = document.getElementById("btnImprimirCracha");
    const btnLimparReutilizacaoInfo = document.getElementById(
      "btnLimparReutilizacaoInfo"
    );

    if (captureBtn)
      captureBtn.addEventListener("click", () => this.capturePhoto());
    if (retakeBtn)
      retakeBtn.addEventListener("click", () => this.retakePhoto(false));
    if (turnOffCamBtn)
      turnOffCamBtn.addEventListener("click", () => this.stopWebcam());
    if (turnOnCamBtn)
      turnOnCamBtn.addEventListener("click", () => this.startWebcam());
    if (btnLimparReutilizacaoInfo)
      btnLimparReutilizacaoInfo.addEventListener("click", () =>
        limparReutilizacao()
      );

    // ✅ CONFIGURAR BOTÃO DE IMPRIMIR NO MODAL DO CRACHÁ
    if (btnImprimirCracha) {
      btnImprimirCracha.addEventListener("click", () => {
        devLog("🖨️ Imprimindo crachá do modal...");
        imprimirCracha();
      });
    }

    const formCadastro = document.getElementById("visitorForm");
    if (formCadastro) {
      formCadastro.addEventListener("submit", (e) => {
        e.preventDefault();
        this.handleSubmit(e);
      });
    }

    const btnLimparTudo = document.querySelector('button[type="reset"]');
    if (btnLimparTudo) {
      btnLimparTudo.addEventListener("click", () => {
        this.limparFormulario();
        limparReutilizacao();
      });
    }
  }

  updateCameraButtons(isCameraOn) {
    const captureBtn = document.getElementById("captureBtn");
    const retakeBtn = document.getElementById("retakeBtn");
    const turnOffCamBtn = document.getElementById("turnOffCamBtn");
    const turnOnCamBtn = document.getElementById("turnOnCamBtn");

    if (captureBtn) captureBtn.style.display = isCameraOn ? "block" : "none";
    if (retakeBtn) retakeBtn.style.display = "none";
    if (turnOffCamBtn) turnOffCamBtn.style.display = isCameraOn ? "block" : "none";
    if (turnOnCamBtn) turnOnCamBtn.style.display = isCameraOn ? "none" : "block";
  }

  async startWebcam() {
    if (this.stream) {
      this.updateCameraButtons(true);
      return;
    }
    await this.initWebcam();
  }

  stopWebcam() {
    if (this.stream) {
      this.stream.getTracks().forEach((track) => track.stop());
      this.stream = null;
    }

    const video = document.getElementById("webcamVideo");
    if (video) {
      video.pause?.();
      video.srcObject = null;
    }

    this.updateCameraButtons(false);
    showToast("Câmera desligada", "info");
  }

  setupReutilizacaoModal() {
    const modalElement = document.getElementById("reutilizarModal");
    const btnReutilizar = document.getElementById("btnReutilizarCadastro");
    const inputBusca = document.getElementById("buscarCadastroModal");
    const btnBuscar = document.getElementById("btnBuscarModal");
    const btnLimpar = document.getElementById("btnLimparBuscaModal");

    if (modalElement) {
      this.reuseModal = new bootstrap.Modal(modalElement);
    }

    if (btnReutilizar) {
      btnReutilizar.addEventListener("click", () => {
        this.resetarModalBusca();
        this.reuseModal?.show();
      });
    }

    if (btnBuscar && inputBusca) {
      btnBuscar.addEventListener("click", () => {
        this.buscarCadastros(inputBusca.value.trim());
      });
    }

    if (btnLimpar) {
      btnLimpar.addEventListener("click", () => this.resetarModalBusca());
    }

    if (inputBusca) {
      inputBusca.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          this.buscarCadastros(inputBusca.value.trim());
        }
      });

      inputBusca.addEventListener("input", (e) => {
        clearTimeout(this.searchTimeout);
        const termo = e.target.value.trim();

        this.searchTimeout = setTimeout(() => {
          if (termo.length >= 2 || termo.length === 0) {
            this.buscarCadastros(termo);
          }
        }, 500);
      });
    }
  }

  resetarModalBusca() {
    const inputBusca = document.getElementById("buscarCadastroModal");
    const resultadosDiv = document.getElementById("resultadosBusca");
    const statusBusca = document.getElementById("statusBuscaModal");

    if (inputBusca) inputBusca.value = "";
    if (resultadosDiv) {
      resultadosDiv.innerHTML = `
        <div class="col-12 text-center text-muted py-4">
          <i class="fas fa-search fa-2x mb-2 d-block"></i>
          Digite acima para buscar cadastros anteriores
        </div>
      `;
    }
    if (statusBusca) {
      statusBusca.textContent = "Digite para buscar cadastros anteriores";
      statusBusca.className = "mt-2 small text-muted";
    }
  }

  async buscarCadastros(termo) {
    const resultadosDiv = document.getElementById("resultadosBusca");
    const statusBusca = document.getElementById("statusBuscaModal");

    if (!resultadosDiv || !statusBusca) return;

    if (!termo || termo.length < 2) {
      this.resetarModalBusca();
      return;
    }

    statusBusca.textContent = "Buscando cadastros...";
    statusBusca.className = "mt-2 small text-info";
    resultadosDiv.innerHTML = `
      <div class="col-12 text-center text-muted py-4">
        <div class="spinner-border spinner-border-sm me-2"></div>
        Buscando cadastros...
      </div>
    `;

    try {
      const response = await fetch(
        `${BASE_URL}index.php?page=api/reactivate_visitor&q=${encodeURIComponent(
          termo
        )}`,
        { credentials: "same-origin" }
      );

      if (!response.ok) {
        throw new Error(`Erro HTTP ${response.status}`);
      }

      const data = await response.json();

      if (!Array.isArray(data) || data.length === 0) {
        resultadosDiv.innerHTML = `
          <div class="col-12 text-center text-muted py-4">
            Nenhum cadastro encontrado
          </div>
        `;
        statusBusca.textContent = `Nenhum resultado para "${termo}"`;
        statusBusca.className = "mt-2 small text-warning";
        return;
      }

      this.exibirResultadosBusca(data, termo);
    } catch (error) {
      devError("❌ Erro na busca de reutilização:", error);
      resultadosDiv.innerHTML = `
        <div class="col-12 text-center text-danger py-4">
          Erro ao buscar cadastros. Tente novamente.
        </div>
      `;
      statusBusca.textContent = "Erro na busca";
      statusBusca.className = "mt-2 small text-danger";
    }
  }

  exibirResultadosBusca(cadastros, termoBusca) {
    const resultadosDiv = document.getElementById("resultadosBusca");
    const statusBusca = document.getElementById("statusBuscaModal");
    if (!resultadosDiv || !statusBusca) return;

    const escapeHtml = (value) =>
      String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");

    let html = "";

    cadastros.forEach((cadastro) => {
      const dataEntrada = cadastro.data_entrada
        ? new Date(cadastro.data_entrada).toLocaleDateString("pt-BR")
        : "Data não disponível";

      const temFoto = cadastro.foto && cadastro.foto !== "" && cadastro.foto !== "N/A";
      const fotoUrl =
        temFoto && cadastro.id
          ? `../api/get_photo.php?vid=${encodeURIComponent(cadastro.id)}`
          : "";
      const fotoHTML = temFoto
        ? `<img src="${fotoUrl}" class="img-thumbnail u-object-cover-50">`
        : `<div class="bg-light rounded d-flex align-items-center justify-content-center u-avatar-placeholder-50">
             <i class="fas fa-user text-muted"></i>
           </div>`;

      html += `
        <div class="col-12">
          <div class="card border-0 shadow-sm mb-2 cadastro-item"
               data-id="${escapeHtml(cadastro.id)}"
               data-nome="${escapeHtml(cadastro.nome || "")}"
               data-cpf="${escapeHtml(cadastro.cpf || "")}"
               data-placa="${escapeHtml(cadastro.placa || "")}"
               data-telefone="${escapeHtml(cadastro.telefone || "")}"
               data-email="${escapeHtml(cadastro.email || "")}"
               data-empresa="${escapeHtml(cadastro.empresa || "")}"
               data-foto="${escapeHtml(cadastro.foto || "")}">
            <div class="card-body py-2">
              <div class="row align-items-center">
                <div class="col-md-1 text-center">${fotoHTML}</div>
                <div class="col-md-7">
                  <h6 class="mb-1 text-primary">${escapeHtml(cadastro.nome || "N/A")}</h6>
                  <p class="mb-1 small text-muted">
                    <strong>CPF:</strong> ${escapeHtml(cadastro.cpf || "N/A")}
                    | <strong>Placa:</strong> ${escapeHtml(cadastro.placa || "N/A")}
                  </p>
                  <p class="mb-0 small text-muted">
                    <strong>Última visita:</strong> ${escapeHtml(dataEntrada)}
                    ${
                      cadastro.empresa
                        ? `<br><strong>Empresa:</strong> ${escapeHtml(cadastro.empresa)}`
                        : ""
                    }
                  </p>
                </div>
                <div class="col-md-4 text-end">
                  <button class="btn btn-sm btn-success btn-selecionar">
                    <i class="fas fa-check me-1"></i> Usar Este
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    resultadosDiv.innerHTML = html;
    statusBusca.textContent = `Encontrados ${cadastros.length} cadastros para "${termoBusca}"`;
    statusBusca.className = "mt-2 small text-success";

    resultadosDiv.querySelectorAll(".btn-selecionar").forEach((btn) => {
      btn.addEventListener("click", function () {
        const card = this.closest(".cadastro-item");
        if (card && window.selecionarCadastro) {
          window.selecionarCadastro(card);
        } else {
          devError("❌ Função selecionarCadastro não encontrada");
        }
      });
    });
  }

  gerarCrachaComDadosAtuais() {
    const nome = document.getElementById("uname")?.value?.trim();
    const cpf = document.getElementById("cpf")?.value?.trim();
    const empresa = document.getElementById("from")?.value?.trim();
    const encontrar = document.getElementById("meet")?.value?.trim();
    const tipoVisita = document.getElementById("representing")?.value;

    if (!nome) {
      showErrorModal(
        "Atenção",
        "Preencha pelo menos o nome do visitante para gerar o crachá.",
        "warning"
      );
      return;
    }

    const dataAtual = new Date().toLocaleDateString("pt-BR");
    const horaAtual = new Date().toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    });

    // Usar foto atual se disponível
    let fotoHTML = "";
    if (this.photoData) {
      fotoHTML = `<img src="${this.photoData}" class="img-fluid rounded u-cracha-photo">`;
    } else {
      fotoHTML = `
          <div class="bg-light rounded d-flex align-items-center justify-content-center u-cracha-photo-placeholder">
              <i class="fas fa-user text-muted fa-3x"></i>
          </div>
      `;
    }

    const crachaHTML = `
      <div class="text-center">
        <h5 class="mb-3"><i class="fas fa-id-card me-2"></i>CRACHÁ VISITANTE</h5>
        
        <div class="foto-visitante mb-3">
          ${fotoHTML}
        </div>
        
        <div class="dados-visitante text-start small">
          <div class="mb-2">
            <strong>Nome:</strong><br>
            <span class="u-cracha-name">${escapeHtml(nome)}</span>
          </div>
          
          ${
            cpf
              ? `
          <div class="mb-2">
            <strong>CPF:</strong><br>
            ${escapeHtml(cpf)}
          </div>
          `
              : ""
          }
          
          ${
            empresa
              ? `
          <div class="mb-2">
            <strong>Empresa:</strong><br>
            ${escapeHtml(empresa)}
          </div>
          `
              : ""
          }
          
          <div class="mb-2">
            <strong>Encontrar com:</strong><br>
            ${escapeHtml(encontrar || "Não informado")}
          </div>
          
          <div class="mb-2">
            <strong>Tipo de Visita:</strong><br>
            ${escapeHtml(tipoVisita || "Não informado")}
          </div>
          
          <div class="row mt-3 small">
            <div class="col-6">
              <strong>Data:</strong><br>${dataAtual}
            </div>
            <div class="col-6">
              <strong>Hora:</strong><br>${horaAtual}
            </div>
          </div>
        </div>
        
        <div class="cracha-footer mt-3 pt-2 border-top small text-muted">
          <i class="fas fa-shield-alt me-1"></i>
          Apresente este crachá durante sua permanência
        </div>
      </div>
    `;

    document.getElementById("badgeModalContent").innerHTML = crachaHTML;

    // Mostrar modal SEM limpeza pendente (é uma geração manual)
    this.pendingClearAfterModal = false;
    this.abrirModalCracha();
  }

  // ✅ MÉTODO CORRIGIDO: Abrir modal do crachá
  abrirModalCracha() {
    const modalElement = document.getElementById("badgeModal");
    if (modalElement) {
      // ✅ USAR bootstrap.Modal DIRETAMENTE (não armazenar em this.currentModal)
      const modal = new bootstrap.Modal(modalElement);

      // ✅ CONFIGURAR EVENTO DE FECHAMENTO CORRETAMENTE
      const handleHidden = () => {
        devLog("🔔 Modal fechado - Verificando se deve limpar formulário");
        if (this.pendingClearAfterModal) {
          devLog("🧹 Limpando formulário após fechar modal do crachá");
          setTimeout(() => {
            this.limparFormularioCompleto();
            this.pendingClearAfterModal = false;
          }, 500);
        }

        // ✅ REMOVER O EVENT LISTENER PARA EVITAR DUPLICAÇÃO
        modalElement.removeEventListener("hidden.bs.modal", handleHidden);
      };

      // ✅ ADICIONAR EVENT LISTENER PARA QUANDO O MODAL FECHAR
      modalElement.addEventListener("hidden.bs.modal", handleHidden);

      // ✅ MOSTRAR MODAL
      modal.show();

      devLog("✅ Modal do crachá aberto com sucesso");
    } else {
      devError("❌ Modal badgeModal não encontrado");
    }
  }

  capturePhoto() {
    const video = document.getElementById("webcamVideo");
    const canvas = document.getElementById("webcamCanvas");
    const ctx = canvas.getContext("2d");

    if (!video || !canvas) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);

    this.photoData = canvas.toDataURL("image/jpeg");
    this.showPhotoPreview();
    showToast("Foto capturada com sucesso!", "success");
  }

  showPhotoPreview() {
    const photoPreview = document.getElementById("photoPreview");
    const captureBtn = document.getElementById("captureBtn");
    const retakeBtn = document.getElementById("retakeBtn");

    if (photoPreview) {
      photoPreview.innerHTML = `
      <div class="text-center">
        <img src="${this.photoData}" class="img-thumbnail" width="150">
        <div class="mt-2 text-success">✅ Foto capturada</div>
      </div>
    `;
    }
    if (captureBtn) captureBtn.style.display = "none";
    if (retakeBtn) retakeBtn.style.display = "inline-block";
  }

  retakePhoto(showToastMessage = true) {
    this.photoData = null;
    const photoPreview = document.getElementById("photoPreview");
    const captureBtn = document.getElementById("captureBtn");
    const retakeBtn = document.getElementById("retakeBtn");

    if (photoPreview) photoPreview.innerHTML = "";
    if (captureBtn) captureBtn.style.display = "inline-block";
    if (retakeBtn) retakeBtn.style.display = "none";
    if (showToastMessage && !this.suppressToasts) {
      showToast("Foto removida", "warning");
    }
  }

  configurarMascaras() {
    const cpfInput = document.getElementById("cpf");
    if (cpfInput) {
      cpfInput.addEventListener("input", function (e) {
        let value = e.target.value.replace(/\D/g, "");
        if (value.length <= 11) {
          value = value.replace(/(\d{3})(\d)/, "$1.$2");
          value = value.replace(/(\d{3})(\d)/, "$1.$2");
          value = value.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
          e.target.value = value;
        }
        validarCPF(e.target);
      });
    }
    const placaInput = document.getElementById("placa");
    if (placaInput) {
      placaInput.addEventListener("input", function (e) {
        let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
        if (value.length > 7) value = value.substring(0, 7);
        if (value.length > 3) {
          value = value.substring(0, 3) + "-" + value.substring(3);
        }
        e.target.value = value;
        validarPlaca(e.target);
      });
    }
    const telefoneInput = document.getElementById("mobileno");
    if (telefoneInput) {
      telefoneInput.addEventListener("input", function (e) {
        let value = e.target.value.replace(/\D/g, "");
        if (value.length <= 11) {
          value = value.replace(/^(\d{2})(\d)/g, "($1) $2");
          value = value.replace(/(\d)(\d{4})$/, "$1-$2");
          e.target.value = value;
        }
      });
    }
  }

  configurarCamposMaiusculos() {
    const campos = [
      { id: "uname", tipo: "uppercase" },
      { id: "meet", tipo: "uppercase" },
      { id: "from", tipo: "uppercase" },
      { id: "email", tipo: "lowercase" },
    ];
    campos.forEach((campo) => {
      const element = document.getElementById(campo.id);
      if (element) {
        element.addEventListener("input", function () {
          this.value =
            campo.tipo === "uppercase"
              ? this.value.toUpperCase()
              : this.value.toLowerCase();
        });
      }
    });
  }

  // ✅ MÉTODO CORRIGIDO: Mostrar modal do crachá
  mostrarModalCracha(visitorId, data) {
    devLog("🖨️ Mostrando modal do crachá para ID:", visitorId);

    const badgeModalContent = document.getElementById("badgeModalContent");
    if (!badgeModalContent) {
      devError("❌ Elemento badgeModalContent não encontrado");
      return;
    }

    const dataAtual = new Date().toLocaleDateString("pt-BR");
    const horaAtual = new Date().toLocaleTimeString("pt-BR", {
      hour: "2-digit",
      minute: "2-digit",
    });

    // ✅ USAR FOTO JÁ CAPTURADA (se disponível)
    let fotoHTML = "";
    if (this.photoData) {
      fotoHTML = `<img src="${this.photoData}" class="img-fluid rounded u-cracha-photo">`;
    } else {
      fotoHTML = `
          <div class="bg-light rounded d-flex align-items-center justify-content-center u-cracha-photo-placeholder">
              <i class="fas fa-user text-muted fa-3x"></i>
          </div>
      `;
    }

    const cpfFormatado = data.cpf
      ? escapeHtml(data.cpf).replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")
      : "";
    const placaFormatada = data.placa
      ? escapeHtml(data.placa).replace(/([A-Z]{3})([A-Z0-9]{4})/, "$1-$2")
      : "";

    const crachaHTML = `
        <div class="text-center">
            <h5 class="mb-3"><i class="fas fa-id-card me-2"></i>CRACHÁ VISITANTE</h5>
            
            <div class="foto-visitante mb-3">
                ${fotoHTML}
            </div>
            
            <div class="dados-visitante text-start small">
                <div class="mb-2">
                    <strong>Nome:</strong><br>
                    <span class="u-cracha-name">${escapeHtml(data.uname)}</span>
                </div>
                
                ${
                  data.cpf
                    ? `
                <div class="mb-2">
                    <strong>CPF:</strong><br>
                    ${cpfFormatado}
                </div>
                `
                    : ""
                }
                
                ${
                  data.placa
                    ? `
                <div class="mb-2">
                    <strong>Placa:</strong><br>
                    ${placaFormatada}
                </div>
                `
                    : ""
                }
                
                ${
                  data.from
                    ? `
                <div class="mb-2">
                    <strong>Empresa:</strong><br>
                    ${escapeHtml(data.from)}
                </div>
                `
                    : ""
                }
                
                <div class="mb-2">
                    <strong>Encontrar com:</strong><br>
                    ${escapeHtml(data.meet || "Não informado")}
                </div>
                
                <div class="mb-2">
                    <strong>Tipo de Visita:</strong><br>
                    ${escapeHtml(data.representing || "Não informado")}
                </div>
                
                <div class="row mt-3 small">
                    <div class="col-6">
                        <strong>Data:</strong><br>${dataAtual}
                    </div>
                    <div class="col-6">
                        <strong>Hora:</strong><br>${horaAtual}
                    </div>
                </div>
            </div>
            
            <div class="cracha-footer mt-3 pt-2 border-top small text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                Apresente este crachá durante sua permanência
            </div>
        </div>
    `;

    badgeModalContent.innerHTML = crachaHTML;

    // ✅ MARCAR QUE DEVE LIMPAR FORMULÁRIO APÓS FECHAR MODAL
    this.pendingClearAfterModal = true;

    // ✅ ABRIR MODAL DO CRACHÁ
    this.abrirModalCracha();
  }

  async handleSubmit(e) {
    if (this.isProcessing) {
      showToast("Aguarde o processamento atual", "warning");
      return;
    }

    const formElement = e?.currentTarget || document.getElementById("visitorForm");
    if (!formElement) {
      showErrorModal("Erro no Cadastro", "Formulário não encontrado", "error");
      return;
    }

    const nome = document.getElementById("uname")?.value?.trim();
    const cpf = document.getElementById("cpf")?.value?.trim();
    const encontrar = document.getElementById("meet")?.value?.trim();
    const tipo = document.getElementById("representing")?.value;

    devLog("🔍 VALIDAÇÃO INICIAL:", {
      nome,
      cpf,
      encontrar,
      tipo,
      currentReusedId,
    });

    if (!nome || !cpf || !encontrar || !tipo) {
      showErrorModal(
        "Atenção",
        "Preencha todos os campos obrigatórios",
        "warning"
      );
      return;
    }

    if (!validarCPF(document.getElementById("cpf"))) {
      showErrorModal("Erro", "CPF inválido", "warning");
      return;
    }

    const placaCampo = document.getElementById("placa");
    if (placaCampo && !validarPlaca(placaCampo)) {
      showErrorModal("Erro", "Placa inválida. Use AAA-0000 ou AAA-0A00.", "warning");
      return;
    }

    // if (!this.photoData) {
    //   showErrorModal("Atenção", "Capture uma foto do visitante", "warning");
    //   return;
    // }
    if (!this.photoData) {
      const confirmacao = await Swal.fire({
        title: "Cadastrar sem foto?",
        html: `
          <div class="text-center">
            <p class="mb-2">⚠️ Nenhuma foto foi capturada.</p>
            <p>Deseja continuar o cadastro mesmo assim?</p>
          </div>
        `,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sim, continuar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#f0ad4e",
        cancelButtonColor: "#6c757d",
        allowOutsideClick: false
      });

      if (!confirmacao.isConfirmed) {
        this.isProcessing = false;
        return;
      }
    }


    this.isProcessing = true;
    const submitter = e?.submitter;
    const btn =
      submitter &&
      submitter.matches &&
      submitter.matches('button[type="submit"], input[type="submit"]')
        ? submitter
        : formElement.querySelector('button[type="submit"], input[type="submit"]');
    const textoOriginal = btn?.innerHTML || "Cadastrar";

    if (btn) {
      btn.innerHTML = "⏳ Cadastrando...";
      btn.disabled = true;
    }

    try {
      const formData = {
        uname: nome.toUpperCase(),
        cpf: cpf.replace(/\D/g, ""),
        placa: (document.getElementById("placa")?.value || "")
          .toUpperCase()
          .replace(/[^A-Z0-9]/g, ""),
        meet: encontrar.toUpperCase(),
        from: (document.getElementById("from")?.value || "").toUpperCase(),
        representing: tipo,
        mobileno: document.getElementById("mobileno")?.value || "",
        email: (document.getElementById("email")?.value || "").toLowerCase(),
        foto:
          typeof this.photoData === "string" &&
          /^data:image\/(?:jpeg|jpg|png|gif);base64,/i.test(this.photoData)
            ? this.photoData
            : null,
      };

      if (currentReusedId) {
        formData.original_id = currentReusedId;
        devLog(
          "🎯 INCLUINDO ID ORIGINAL PARA REUTILIZAÇÃO:",
          currentReusedId
        );
      }

      devLog("📤 Enviando dados para API:", formData);

      if (this.photoData) {
          devLog("📸 Tamanho da foto:", this.photoData.length);
          devLog("📸 Tipo da foto:", typeof this.photoData);
      } else {
          devLog("📸 Cadastro sem foto");
      }


      const response = await fetch(
        `${BASE_URL}index.php?page=api/api_cadastro_visitante`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": CSRF_TOKEN,
          },
          credentials: "same-origin",
          body: JSON.stringify(formData),
        }
      );

      devLog("📥 Resposta da API - Status:", response.status);
      devLog("📥 Resposta da API - OK:", response.ok);

      const responseText = await response.text();
      devLog("📥 Resposta da API - Texto COMPLETO:", responseText);

      let resultado;

      // ✅ CORREÇÃO: Verificar se a resposta começa com HTML/erro
      if (
        responseText.trim().startsWith("<") ||
        responseText.includes("<br />")
      ) {
        devError("❌ API retornando HTML/erro em vez de JSON");

        // Extrair mensagem de erro do PHP se possível
        const errorMatch = responseText.match(/<b>(.*?)<\/b>/);
        const errorMessage = errorMatch
          ? `Erro PHP: ${errorMatch[1]}`
          : "Erro no servidor - contate o administrador";

        throw new Error(errorMessage);
      }

      try {
        resultado = JSON.parse(responseText);
        devLog("✅ JSON parseado com sucesso:", resultado);
      } catch (parseError) {
        devError("❌ Erro ao parsear JSON:", parseError);
        throw new Error(
          "Resposta inválida do servidor - formato JSON corrompido"
        );
      }

      devLog("📊 Resultado da API:", resultado);

      if (resultado.success) {
        const newVisitorId = resultado.id;
        devLog("✅ CADASTRO BEM SUCEDIDO - ID:", newVisitorId);

        await showSuccessModal(
          currentReusedId ? "Nova Visita Registrada" : "Cadastro Realizado",
          resultado.message ||
            `Visitante cadastrado com sucesso! ID: ${newVisitorId}`
        );

        // ✅ MOSTRAR CRACHÁ APÓS CADASTRO BEM-SUCEDIDO
        this.mostrarModalCracha(newVisitorId || "temp", formData);

        devLog(
          "📋 Formulário mantido - será limpo após fechar modal do crachá"
        );
      } else {
        const apiError = resultado.error || resultado.message || "Erro desconhecido";
        devError("❌ Erro no resultado da API:", apiError);

        if (
          apiError.includes("ativo com este CPF") ||
          apiError.includes("ativo para esta placa")
        ) {
          const match = apiError.match(
            /(?:para esta placa|com este CPF): (.+?) \(ID: (\d+)\)/
          );

          if (match) {
            const conflictType = apiError.includes("CPF")
              ? "cpf"
              : "placa";
            const visitorName = match[2];
            const visitorId = match[3];
            const conflictValue =
              conflictType === "cpf"
                ? document.getElementById("cpf")?.value || "N/A"
                : document.getElementById("placa")?.value || "N/A";

            showActiveVisitorModal(
              visitorName,
              visitorId,
              conflictType,
              conflictValue
            );
          } else {
            showErrorModal("Conflito de Visita", apiError, "warning");
          }
        } else {
          showErrorModal("Erro no Cadastro", apiError, "error");
        }
      }
    } catch (error) {
      devError("❌ Erro no cadastro:", error);
      showErrorModal("Erro no Cadastro", error.message, "error");
    } finally {
      this.isProcessing = false;
      if (btn) {
        btn.innerHTML = textoOriginal;
        btn.disabled = false;
      }
    }
  }

  limparFormularioCompleto() {
    devLog("🧹 INICIANDO LIMPEZA COMPLETA DO FORMULÁRIO");

    try {
      // Limpar formulário
      const form = document.getElementById("visitorForm");
      if (form) {
        form.reset();
        devLog("✅ Formulário resetado");
      }

      // Limpar foto
      this.suppressToasts = true;
      this.retakePhoto(false);
      this.suppressToasts = false;
      devLog("✅ Foto removida");

      // Limpar validações visuais
      document.querySelectorAll(".is-valid, .is-invalid").forEach((el) => {
        el.classList.remove("is-valid", "is-invalid");
      });
      devLog("✅ Estados de validação limpos");

      // Limpar reutilização
      limparReutilizacao();
      devLog("✅ Dados de reutilização limpos");

      // Focar no campo nome
      setTimeout(() => {
        const nomeInput = document.getElementById("uname");
        if (nomeInput) {
          nomeInput.focus();
          devLog("✅ Foco definido no campo nome");
        }
      }, 300);

      devLog(
        "🎯 Formulário completamente limpo - PRONTO PARA NOVO CADASTRO"
      );
      showToast("Formulário limpo com sucesso!", "success");
    } catch (error) {
      devError("❌ Erro ao limpar formulário:", error);
    }
  }

  limparFormulario() {
    this.limparFormularioCompleto();
  }
}

// ✅ INICIALIZAR SISTEMA
document.addEventListener("DOMContentLoaded", () => {
  window.visitorSystem = new VisitorSystem();
});
