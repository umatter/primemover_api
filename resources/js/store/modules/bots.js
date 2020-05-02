// state
import axios from "axios";
import * as types from "../mutation-types";

export const state = {
  configurations: null
}

// getters
export const getters = {
  configurations: state => state.configurations
}

// mutations
export const mutations = {
  [types.FETCH_CONFIGURATIONS_SUCCESS](state, {response}) {
    state.configurations = response.configurations
  },

  [types.DESTROY_CONFIGURATION_SUCCESS](state, {response}) {
    state.configurations = response
  },

  [types.DESTROY_CONFIGURATION_FAILURE](state) {
    console.log(state);
  },

  [types.CREATE_CONFIGURATION_SUCCESS](state, {response}) {
    state.configurations = response
  },

  [types.CREATE_CONFIGURATION_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_CONFIGURATIONS_FAILURE](state) {
    state.configurations = null
  },

}

// actions
export const actions = {
  async fetchConfigurations({commit}) {
    try {
      const {data} = await axios.get('/api/configurations')

      commit(types.FETCH_CONFIGURATIONS_SUCCESS, {response: data})
    } catch (e) {
      commit(types.FETCH_CONFIGURATIONS_FAILURE)
    }
  },
  async destroyConfiguration({commit}, payload) {

    try {
      const {data} = await axios.delete('/api/configurations/' + payload.id)
      commit(types.DESTROY_CONFIGURATION_SUCCESS, {response: data})
    } catch (e) {
      commit(types.DESTROY_CONFIGURATION_FAILURE)
    }
  },
  async createConfiguration({commit}, payload) {

    try {
      const {data} = await axios.post('/api/configurations/' + payload)
      console.log(payload, data);
      //commit(types.CREATE_CONFIGURATION_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_CONFIGURATION_FAILURE)
    }
  },
}
