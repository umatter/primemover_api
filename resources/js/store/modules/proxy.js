// state
import axios from "axios";
import * as types from "../mutation-types";

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
  [types.FETCH_PROXIES_SUCCESS](state, {response}) {
    state.records = response.proxies;
    state.count = response.proxies.length
  },

  [types.DESTROY_PROXY_SUCCESS](state, {response}) {
    console.log(response);
    state.records = response;
    state.count = response.length
  },

  [types.DESTROY_PROXY_FAILURE](state) {
    console.log(state);
  },

  [types.CREATE_PROXY_SUCCESS](state, {response}) {
    state.records = response
  },

  [types.CREATE_PROXY_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_PROXIES_FAILURE](state) {
    state.records = null
  },

}

// actions
export const actions = {
  async fetchRecords({commit}) {
    try {
      const {data} = await axios.get('/api/proxies')

      commit(types.FETCH_PROXIES_SUCCESS, {response: data})
    } catch (e) {
      commit(types.FETCH_PROXIES_FAILURE)
    }

  },
  async destroyRecord({commit}, payload) {
  //console.log(commit, payload)
    try {
      const {data} = await axios.delete('/api/proxies/' + payload.id)
      commit(types.DESTROY_PROXY_SUCCESS, {response: data})
    } catch (e) {
      commit(types.DESTROY_PROXY_FAILURE)
    }
  },
  async createRecord({commit}, payload) {

    try {
      const {data} = await axios.post('/api/proxies/' + payload)
      console.log(payload, data);
      //commit(types.CREATE_PROXY_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_PROXY_FAILURE)
    }
  },
}
