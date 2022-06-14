// state
import axios from 'axios'
import * as types from '../mutation-types'
import { apiDomain } from '../../config'

export const state = {
  records: null,
  count: 0
}

// getters
export const getters = {
  records: state => state.records,
  count: state => state.count
}

// mutations
export const mutations = {
  [types.FETCH_EXPERIMENTS_SUCCESS] (state, { response }) {
    state.records = response.data
    state.count = response.data.length
  },

  [types.DESTROY_EXPERIMENT_SUCCESS] (state, { response }) {
    console.log(response)
    state.records = response
    state.count = response.length
  },

  [types.DESTROY_EXPERIMENT_FAILURE] (state) {
    console.log(state)
  },

  [types.CREATE_EXPERIMENT_SUCCESS] (state, { response }) {
    state.records = response
  },

  [types.CREATE_EXPERIMENT_FAILURE] (state) {
    console.log(state)
  },

  [types.FETCH_EXPERIMENTS_FAILURE] (state) {
    state.records = null
  }

}

// actions
export const actions = {
  async fetchRecords ({ commit }) {
    try {
      const { data } = await axios.get(apiDomain + 'experiments')

      commit(types.FETCH_EXPERIMENTS_SUCCESS, { response: data })
    } catch (e) {
      commit(types.FETCH_EXPERIMENTS_FAILURE)
    }
  },
  async destroyRecord ({ commit }, payload) {
    // console.log(commit, payload)
    try {
      const { data } = await axios.delete(apiDomain + 'experiments/' + payload.id)
      commit(types.DESTROY_EXPERIMENT_SUCCESS, { response: data })
    } catch (e) {
      commit(types.DESTROY_EXPERIMENT_FAILURE)
    }
  }

}
