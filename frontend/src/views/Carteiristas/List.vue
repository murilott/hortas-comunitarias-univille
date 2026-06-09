<template>
  <div class="container-fluid px-4 mt-4">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-start mb-4">
      <div>
        <h2 class="fw-bold mb-1">Canteiristas</h2>
        <p class="text-muted mb-0">Gerencie os canteiristas cadastrados no sistema</p>
      </div>
      <button class="btn btn-success d-flex align-items-center gap-2" @click="abrirModalCriar">
        <i class="fas fa-plus"></i> Novo Canteirista
      </button>
    </div>

    <!-- Mini-dash -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Total de Canteiristas</p>
              <h3 class="fw-bold text-success mb-0">{{ total }}</h3>
            </div>
            <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
              <i class="fas fa-users text-success"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Canteiristas Ativos</p>
              <h3 class="fw-bold text-success mb-0">{{ totalAtivos }}</h3>
            </div>
            <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
              <i class="fas fa-map-marker-alt text-success"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted small mb-1">Média de Canteiros</p>
              <h3 class="fw-bold text-warning mb-0">{{ mediaCanteiros }}</h3>
            </div>
            <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px">
              <i class="fas fa-seedling text-warning"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Busca + Filtros -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body d-flex gap-3 align-items-center">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0">
            <i class="fas fa-search text-muted"></i>
          </span>
          <input
            v-model="busca"
            type="text"
            class="form-control border-start-0 ps-0"
            placeholder="Buscar por nome, CPF ou email..."
          />
        </div>
        <button class="btn btn-outline-secondary d-flex align-items-center gap-2 text-nowrap" @click="mostrarFiltros = !mostrarFiltros">
          <i class="fas fa-filter"></i> Filtros
        </button>
      </div>
      <div v-if="mostrarFiltros" class="card-body border-top pt-3">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small text-muted">Status</label>
            <select v-model="filtroStatus" class="form-select form-select-sm">
              <option value="">Todos</option>
              <option value="1">Ativo</option>
              <option value="0">Inativo</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Horta Vinculada</label>
            <select v-model="filtroHorta" class="form-select form-select-sm">
              <option value="">Todas</option>
              <option v-for="h in hortas" :key="h.id" :value="h.id">{{ h.nome }}</option>
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-sm btn-outline-secondary w-100" @click="limparFiltros">
              <i class="fas fa-times me-1"></i> Limpar filtros
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-success" role="status">
        <span class="visually-hidden">Carregando...</span>
      </div>
    </div>

    <!-- Tabela -->
    <div v-else class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="px-4 py-3 border-bottom">
          <h6 class="mb-0 fw-semibold">Lista de Canteiristas</h6>
        </div>

        <div v-if="listaFiltrada.length === 0" class="text-center py-5 text-muted">
          <i class="fas fa-users fa-2x mb-3 d-block opacity-25"></i>
          Nenhum canteirista encontrado.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="px-4">Nome</th>
                <th>CPF</th>
                <th>Contato</th>
                <th>Horta Vinculada</th>
                <th>Canteiros</th>
                <th>Status</th>
                <th class="text-end px-4">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in listaFiltrada" :key="c.id">
                <td class="px-4">
                  <div class="fw-semibold">{{ c.usuario?.nome_completo || '-' }}</div>
                  <div class="text-muted small">{{ c.usuario?.email || '-' }}</div>
                </td>
                <td class="text-muted small">{{ formatarCpf(c.usuario?.cpf) }}</td>
                <td>
                  <div class="small">{{ c.telefone || '-' }}</div>
                </td>
                <td>
                  <span v-if="nomeHorta(c.horta_vinculada)" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-normal px-2 py-1">
                    {{ nomeHorta(c.horta_vinculada) }}
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td>
                  <div class="d-flex flex-wrap gap-1 mb-1">
                    <span
                      v-for="canteiro in c.canteiros.slice(0, 3)"
                      :key="canteiro.uuid"
                      class="badge bg-secondary bg-opacity-15 text-dark fw-normal"
                    >
                      #{{ String(canteiro.numero_identificador ?? canteiro.nome ?? '?').padStart(2, '0') }}
                    </span>
                    <span v-if="c.canteiros.length > 3" class="badge bg-secondary bg-opacity-15 text-muted fw-normal">
                      +{{ c.canteiros.length - 3 }}
                    </span>
                  </div>
                  <div class="text-muted" style="font-size:0.7rem">{{ c.canteiros.length }} canteiro(s)</div>
                </td>
                <td>
                  <span :class="c.ativo ? 'badge bg-dark text-white' : 'badge bg-light text-dark border'">
                    {{ c.ativo ? 'Ativo' : 'Inativo' }}
                  </span>
                </td>
                <td class="text-end px-4">
                  <button class="btn btn-sm btn-link text-secondary p-1 me-1" title="Visualizar" @click="abrirModalVer(c)">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="btn btn-sm btn-link text-secondary p-1 me-1" title="Editar" @click="abrirModalEditar(c)">
                    <i class="fas fa-pen-to-square"></i>
                  </button>
                  <button class="btn btn-sm btn-link text-danger p-1" title="Excluir" @click="abrirModalExcluir(c)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===== MODAL CRIAR ===== -->
    <div v-if="modalCriar" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:480px">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">Cadastrar Novo Canteirista</h5>
            <button type="button" class="btn-close" @click="modalCriar=false"></button>
          </div>
          <div class="modal-body">
            <div v-if="erroModal" class="alert alert-danger py-2 small">{{ erroModal }}</div>
            <div class="row g-3" autocomplete="off">
              <div class="col-6">
                <label class="form-label small fw-semibold">CPF <span class="text-danger">*</span></label>
                <input v-model="form.cpf" type="text" class="form-control" :class="{'is-invalid':erros.cpf}" placeholder="000.000.000-00" @input="mascaraCpf" maxlength="14" autocomplete="off" />
                <div v-if="erros.cpf" class="invalid-feedback">{{ erros.cpf }}</div>
                <div v-else class="form-text text-muted" style="font-size:0.7rem">CPF com dígitos verificadores válidos</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Nome Completo <span class="text-danger">*</span></label>
                <input v-model="form.nome_completo" type="text" class="form-control" :class="{'is-invalid':erros.nome_completo}" placeholder="Nome completo" autocomplete="off" />
                <div v-if="erros.nome_completo" class="invalid-feedback">{{ erros.nome_completo }}</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Telefone <span class="text-danger">*</span></label>
                <input v-model="form.telefone" type="text" class="form-control" :class="{'is-invalid':erros.telefone}" placeholder="(00) 00000-0000" @input="mascaraTelefone" maxlength="15" autocomplete="off" />
                <div v-if="erros.telefone" class="invalid-feedback">{{ erros.telefone }}</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                <input v-model="form.email" type="text" class="form-control" :class="{'is-invalid':erros.email}" placeholder="email@exemplo.com" autocomplete="off" />
                <div v-if="erros.email" class="invalid-feedback">{{ erros.email }}</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Senha <span class="text-danger">*</span></label>
                <input v-model="form.senha" type="password" class="form-control" :class="{'is-invalid':erros.senha}" placeholder="Mínimo 6 caracteres" autocomplete="new-password" />
                <div v-if="erros.senha" class="invalid-feedback">{{ erros.senha }}</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Apelido <span class="text-danger">*</span></label>
                <input v-model="form.apelido" type="text" class="form-control" :class="{'is-invalid':erros.apelido}" placeholder="Como preferir ser chamado" autocomplete="off" />
                <div v-if="erros.apelido" class="invalid-feedback">{{ erros.apelido }}</div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Data de Nascimento <span class="text-danger">*</span></label>
                <input v-model="form.data_de_nascimento" type="date" class="form-control" :class="{'is-invalid':erros.data_de_nascimento}" />
                <div v-if="erros.data_de_nascimento" class="invalid-feedback">{{ erros.data_de_nascimento }}</div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Horta Vinculada <span class="text-danger">*</span></label>
                <select v-model="form.horta_uuid" class="form-select" :class="{'is-invalid':erros.horta_uuid}" @change="carregarCanteiros(form.horta_uuid, 'criar')">
                  <option value="">Selecionar horta</option>
                  <option v-for="h in hortas" :key="h.id" :value="h.id">{{ h.nome }}</option>
                </select>
                <div v-if="erros.horta_uuid" class="invalid-feedback">{{ erros.horta_uuid }}</div>
              </div>
              <div v-if="canteirosCriar.length > 0" class="col-12">
                <label class="form-label small fw-semibold">Atribuir Canteiros</label>
                <div class="border rounded p-2" style="max-height:150px;overflow-y:auto">
                  <div class="row g-1">
                    <div v-for="canteiro in canteirosCriar" :key="canteiro.id" class="col-3">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" :id="`c-criar-${canteiro.id}`" :value="canteiro.id" v-model="form.canteiros_uuids" />
                        <label class="form-check-label small" :for="`c-criar-${canteiro.id}`">
                          #{{ String(canteiro.nome ?? '?').padStart(2, '0') }}
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button class="btn btn-outline-secondary" @click="modalCriar=false" :disabled="salvando">Cancelar</button>
            <button class="btn btn-success" @click="cadastrar" :disabled="salvando">
              <span v-if="salvando" class="spinner-border spinner-border-sm me-1"></span>
              Cadastrar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL VISUALIZAR ===== -->
    <div v-if="modalVer" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:500px">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">Detalhes do Canteirista</h5>
            <button type="button" class="btn-close" @click="modalVer=false"></button>
          </div>
          <div v-if="itemSelecionado" class="modal-body">
            <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded">
              <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-5" style="width:50px;height:50px;flex-shrink:0">
                {{ iniciais(itemSelecionado.usuario?.nome_completo) }}
              </div>
              <div>
                <div class="fw-bold fs-6">{{ itemSelecionado.usuario?.nome_completo || '—' }}</div>
                <div class="text-muted small">{{ itemSelecionado.usuario?.email || '—' }}</div>
                <span :class="itemSelecionado.ativo ? 'badge bg-success mt-1' : 'badge bg-secondary mt-1'">
                  {{ itemSelecionado.ativo ? 'Ativo' : 'Inativo' }}
                </span>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-6">
                <p class="text-muted small mb-1">CPF</p>
                <p class="mb-0 fw-semibold">{{ formatarCpf(itemSelecionado.usuario?.cpf) }}</p>
              </div>
              <div class="col-6">
                <p class="text-muted small mb-1">Apelido</p>
                <p class="mb-0 fw-semibold">{{ itemSelecionado.usuario?.apelido || '—' }}</p>
              </div>
              <div class="col-6">
                <p class="text-muted small mb-1">Telefone</p>
                <p class="mb-0 fw-semibold">{{ itemSelecionado.telefone || '—' }}</p>
              </div>
              <div class="col-6">
                <p class="text-muted small mb-1">Data de Nascimento</p>
                <p class="mb-0 fw-semibold">{{ formatarData(itemSelecionado.usuario?.data_de_nascimento) }}</p>
              </div>
              <div class="col-12">
                <p class="text-muted small mb-1">Horta Vinculada</p>
                <span v-if="nomeHorta(itemSelecionado.horta_vinculada)" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-normal px-2 py-1">
                  {{ nomeHorta(itemSelecionado.horta_vinculada) }}
                </span>
                <span v-else class="text-muted">—</span>
              </div>
              <div class="col-12">
                <p class="text-muted small mb-1">Canteiros ({{ itemSelecionado.canteiros?.length || 0 }})</p>
                <div class="d-flex flex-wrap gap-1">
                  <span v-for="canteiro in itemSelecionado.canteiros" :key="canteiro.uuid" class="badge bg-secondary bg-opacity-15 text-dark fw-normal">
                    #{{ String(canteiro.numero_identificador ?? canteiro.nome ?? '?').padStart(2, '0') }}
                  </span>
                  <span v-if="!itemSelecionado.canteiros?.length" class="text-muted small">Nenhum canteiro atribuído</span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button class="btn btn-outline-secondary" @click="modalVer=false">Fechar</button>
            <button class="btn btn-primary" @click="modalVer=false; abrirModalEditar(itemSelecionado)">
              <i class="fas fa-pen-to-square me-1"></i> Editar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR ===== -->
    <div v-if="modalEditar" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:480px">
        <div class="modal-content border-0 shadow">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">Editar Canteirista</h5>
            <button type="button" class="btn-close" @click="modalEditar=false"></button>
          </div>
          <div class="modal-body">
            <div v-if="erroModal" class="alert alert-danger py-2 small">{{ erroModal }}</div>
            <div class="row g-3">
              <div class="col-6">
                <label class="form-label small fw-semibold">Nome Completo</label>
                <input v-model="formEditar.nome_completo" type="text" class="form-control" :class="{'is-invalid':errosEditar.nome_completo}" autocomplete="off" />
                <div v-if="errosEditar.nome_completo" class="invalid-feedback">{{ errosEditar.nome_completo }}</div>
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Apelido</label>
                <input v-model="formEditar.apelido" type="text" class="form-control" autocomplete="off" />
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Telefone</label>
                <input v-model="formEditar.telefone" type="text" class="form-control" @input="mascaraTelefoneEditar" maxlength="15" autocomplete="off" />
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Email</label>
                <input v-model="formEditar.email" type="text" class="form-control" :class="{'is-invalid':errosEditar.email}" autocomplete="off" />
                <div v-if="errosEditar.email" class="invalid-feedback">{{ errosEditar.email }}</div>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Data de Nascimento</label>
                <input v-model="formEditar.data_de_nascimento" type="date" class="form-control" />
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Horta Vinculada</label>
                <select v-model="formEditar.horta_uuid" class="form-select" @change="carregarCanteiros(formEditar.horta_uuid, 'editar')">
                  <option value="">Selecionar horta</option>
                  <option v-for="h in hortas" :key="h.id" :value="h.id">{{ h.nome }}</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Status</label>
                <select v-model="formEditar.ativo" class="form-select">
                  <option :value="true">Ativo</option>
                  <option :value="false">Inativo</option>
                </select>
              </div>
              <div v-if="canteirosEditar.length > 0" class="col-12">
                <label class="form-label small fw-semibold">Canteiros Atribuídos</label>
                <div class="border rounded p-2" style="max-height:150px;overflow-y:auto">
                  <div class="row g-1">
                    <div v-for="canteiro in canteirosEditar" :key="canteiro.id" class="col-3">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" :id="`c-editar-${canteiro.id}`" :value="canteiro.id" v-model="formEditar.canteiros_uuids" />
                        <label class="form-check-label small" :for="`c-editar-${canteiro.id}`">
                          #{{ String(canteiro.nome ?? '?').padStart(2, '0') }}
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button class="btn btn-outline-secondary" @click="modalEditar=false" :disabled="salvando">Cancelar</button>
            <button class="btn btn-success" @click="salvarEdicao" :disabled="salvando">
              <span v-if="salvando" class="spinner-border spinner-border-sm me-1"></span>
              Salvar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EXCLUIR ===== -->
    <div v-if="modalExcluir" class="modal d-block" tabindex="-1" style="background:rgba(0,0,0,0.4)">
      <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content border-0 shadow">
          <div class="modal-body text-center py-4 px-4">
            <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:60px;height:60px">
              <i class="fas fa-trash-alt text-danger fs-4"></i>
            </div>
            <h5 class="fw-bold mb-2">Excluir Canteirista</h5>
            <p class="text-muted mb-0">
              Tem certeza que deseja excluir <strong>{{ itemSelecionado?.usuario?.nome_completo }}</strong>?
            </p>
            <p class="text-muted small">Esta ação não pode ser desfeita.</p>
          </div>
          <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
            <button class="btn btn-outline-secondary px-4" @click="modalExcluir=false" :disabled="salvando">Cancelar</button>
            <button class="btn btn-danger px-4" @click="confirmarExcluir" :disabled="salvando">
              <span v-if="salvando" class="spinner-border spinner-border-sm me-1"></span>
              Excluir
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'
import hortasService from '@/services/hortas.service'
import canteirosService from '@/services/canteiros.service'

export default {
  name: 'CanteiristasList',
  setup() {
    const store = useStore()

    const carteiristas = computed(() => store.getters['carteiristas/allCarteiristas'])
    const isLoading = computed(() => store.getters['carteiristas/isLoading'])

    const busca = ref('')
    const filtroStatus = ref('')
    const filtroHorta = ref('')
    const mostrarFiltros = ref(false)
    const hortas = ref([])

    const canteirosCriar = ref([])
    const canteirosEditar = ref([])

    const modalCriar = ref(false)
    const modalVer = ref(false)
    const modalEditar = ref(false)
    const modalExcluir = ref(false)
    const itemSelecionado = ref(null)
    const salvando = ref(false)
    const erroModal = ref('')

    const formInicial = () => ({
      cpf: '', nome_completo: '', telefone: '', email: '',
      senha: '', data_de_nascimento: '', apelido: '',
      horta_uuid: '', canteiros_uuids: [],
    })

    const form = ref(formInicial())
    const erros = ref({})

    const formEditar = ref({})
    const errosEditar = ref({})

    onMounted(async () => {
      store.dispatch('carteiristas/fetchCarteiristas')
      try {
        const res = await hortasService.getAll()
        hortas.value = res.data || []
      } catch {
        hortas.value = []
      }
    })

    const total = computed(() => carteiristas.value.length)
    const totalAtivos = computed(() => carteiristas.value.filter(c => c.ativo).length)
    const mediaCanteiros = computed(() => {
      if (!carteiristas.value.length) return '0.0'
      const soma = carteiristas.value.reduce((acc, c) => acc + (c.canteiros?.length || 0), 0)
      return (soma / carteiristas.value.length).toFixed(1)
    })

    const listaFiltrada = computed(() => {
      const termo = busca.value.toLowerCase()
      return carteiristas.value.filter(c => {
        const nomeMatch = !termo ||
          c.usuario?.nome_completo?.toLowerCase().includes(termo) ||
          c.usuario?.cpf?.includes(termo) ||
          c.usuario?.email?.toLowerCase().includes(termo)
        const statusMatch = filtroStatus.value === '' || String(c.ativo ? 1 : 0) === filtroStatus.value
        const hortaMatch = !filtroHorta.value || c.horta_vinculada === filtroHorta.value
        return nomeMatch && statusMatch && hortaMatch
      })
    })

    const nomeHorta = (uuid) => {
      if (!uuid) return null
      return hortas.value.find(h => h.id === uuid)?.nome || null
    }

    const formatarCpf = (cpf) => {
      if (!cpf) return '-'
      const s = cpf.replace(/\D/g, '')
      if (s.length !== 11) return cpf
      return s.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
    }

    const formatarData = (data) => {
      if (!data) return '—'
      const d = new Date(data)
      if (isNaN(d)) return data
      return d.toLocaleDateString('pt-BR', { timeZone: 'UTC' })
    }

    const iniciais = (nome) => {
      if (!nome) return '?'
      return nome.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
    }

    const limparFiltros = () => { busca.value = ''; filtroStatus.value = ''; filtroHorta.value = '' }

    const carregarCanteiros = async (hortaUuid, contexto) => {
      if (contexto === 'criar') { form.value.canteiros_uuids = []; canteirosCriar.value = [] }
      else { formEditar.value.canteiros_uuids = []; canteirosEditar.value = [] }

      if (!hortaUuid) return
      try {
        const res = await canteirosService.getAll({ horta_uuid: hortaUuid })
        if (contexto === 'criar') canteirosCriar.value = res.data || []
        else canteirosEditar.value = res.data || []
      } catch {
        // silencioso
      }
    }

    const mascaraCpf = () => {
      let v = form.value.cpf.replace(/\D/g, '').slice(0, 11)
      v = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')
      form.value.cpf = v
    }

    const mascaraTelefone = () => {
      let v = form.value.telefone.replace(/\D/g, '').slice(0, 11)
      form.value.telefone = v.length > 10
        ? v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
        : v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3').replace(/-$/, '')
    }

    const mascaraTelefoneEditar = () => {
      let v = formEditar.value.telefone.replace(/\D/g, '').slice(0, 11)
      formEditar.value.telefone = v.length > 10
        ? v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
        : v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3').replace(/-$/, '')
    }

    // ---- CRUD handlers ----

    const abrirModalCriar = () => {
      form.value = formInicial()
      erros.value = {}
      erroModal.value = ''
      canteirosCriar.value = []
      modalCriar.value = true
    }

    const abrirModalVer = (item) => {
      itemSelecionado.value = item
      modalVer.value = true
    }

    const abrirModalEditar = async (item) => {
      itemSelecionado.value = item
      errosEditar.value = {}
      erroModal.value = ''
      formEditar.value = {
        nome_completo: item.usuario?.nome_completo || '',
        apelido: item.usuario?.apelido || '',
        email: item.usuario?.email || '',
        telefone: item.telefone || '',
        data_de_nascimento: item.usuario?.data_de_nascimento?.substring(0, 10) || '',
        horta_uuid: item.horta_vinculada || '',
        ativo: item.ativo,
        canteiros_uuids: item.canteiros?.map(c => c.uuid) || [],
      }
      canteirosEditar.value = []
      if (item.horta_vinculada) {
        await carregarCanteiros(item.horta_vinculada, 'editar')
        formEditar.value.canteiros_uuids = item.canteiros?.map(c => c.uuid) || []
      }
      modalEditar.value = true
    }

    const abrirModalExcluir = (item) => {
      itemSelecionado.value = item
      modalExcluir.value = true
    }

    const validarCriar = () => {
      const e = {}
      if (!form.value.cpf) e.cpf = 'CPF é obrigatório'
      if (!form.value.nome_completo) e.nome_completo = 'Nome é obrigatório'
      if (!form.value.telefone) e.telefone = 'Telefone é obrigatório'
      if (!form.value.email) e.email = 'Email é obrigatório'
      if (!form.value.senha) e.senha = 'Senha é obrigatória'
      if (!form.value.apelido) e.apelido = 'Apelido é obrigatório'
      if (!form.value.data_de_nascimento) e.data_de_nascimento = 'Data de nascimento é obrigatória'
      if (!form.value.horta_uuid) e.horta_uuid = 'Horta é obrigatória'
      erros.value = e
      return Object.keys(e).length === 0
    }

    const cadastrar = async () => {
      if (!validarCriar()) return
      salvando.value = true
      erroModal.value = ''
      try {
        const payload = {
          cpf: form.value.cpf.replace(/\D/g, ''),
          nome_completo: form.value.nome_completo,
          telefone: form.value.telefone,
          email: form.value.email,
          senha: form.value.senha,
          data_de_nascimento: form.value.data_de_nascimento,
          apelido: form.value.apelido,
          horta_uuid: form.value.horta_uuid,
          canteiros: form.value.canteiros_uuids,
        }
        const res = await store.dispatch('carteiristas/createCarteirista', payload)
        if (res.success) {
          modalCriar.value = false
        } else {
          erroModal.value = res.message || 'Erro ao cadastrar canteirista'
        }
      } finally {
        salvando.value = false
      }
    }

    const salvarEdicao = async () => {
      if (!itemSelecionado.value) return
      salvando.value = true
      erroModal.value = ''
      try {
        const payload = {
          nome_completo: formEditar.value.nome_completo,
          apelido: formEditar.value.apelido,
          email: formEditar.value.email,
          telefone: formEditar.value.telefone,
          data_de_nascimento: formEditar.value.data_de_nascimento,
          horta_uuid: formEditar.value.horta_uuid,
          ativo: formEditar.value.ativo ? 1 : 0,
          canteiros: formEditar.value.canteiros_uuids,
        }
        const res = await store.dispatch('carteiristas/updateCarteirista', { id: itemSelecionado.value.id, data: payload })
        if (res.success) {
          modalEditar.value = false
          store.dispatch('carteiristas/fetchCarteiristas')
        } else {
          erroModal.value = res.message || 'Erro ao atualizar canteirista'
        }
      } finally {
        salvando.value = false
      }
    }

    const confirmarExcluir = async () => {
      if (!itemSelecionado.value) return
      salvando.value = true
      try {
        const res = await store.dispatch('carteiristas/deleteCarteirista', itemSelecionado.value.id)
        if (res.success) {
          modalExcluir.value = false
        } else {
          alert(res.message)
        }
      } finally {
        salvando.value = false
      }
    }

    return {
      carteiristas, isLoading, busca, filtroStatus, filtroHorta, mostrarFiltros,
      hortas, canteirosCriar, canteirosEditar,
      total, totalAtivos, mediaCanteiros, listaFiltrada,
      nomeHorta, formatarCpf, formatarData, iniciais, limparFiltros,
      modalCriar, modalVer, modalEditar, modalExcluir,
      itemSelecionado, salvando, erroModal, erros, errosEditar,
      form, formEditar,
      abrirModalCriar, abrirModalVer, abrirModalEditar, abrirModalExcluir,
      carregarCanteiros, mascaraCpf, mascaraTelefone, mascaraTelefoneEditar,
      cadastrar, salvarEdicao, confirmarExcluir,
    }
  }
}
</script>

<style scoped>
.modal { overflow-y: auto; }
.table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6c757d; font-weight: 600; }
.badge { font-size: 0.72rem; }
.btn-link { text-decoration: none; font-size: 1rem; }
.btn-link:hover { opacity: 0.75; }
</style>
