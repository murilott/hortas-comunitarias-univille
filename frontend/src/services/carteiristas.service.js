import api from './api'

export default {
  getAll(params = {}) {
    return api.get('/Canteiristas', { params })
  },

  getById(id) {
    return api.get(`/Canteiristas/${id}`)
  },

  create(data) {
    return api.post('/Canteiristas', data)
  },

  update(id, data) {
    return api.put(`/Canteiristas/${id}`, data)
  },

  delete(id) {
    return api.delete(`/Canteiristas/${id}`)
  }
}
