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
  [types.FETCH_AGENTS_SUCCESS](state, {response}) {
    state.records = response.agents;
    state.count = response.agents.length
  },

  [types.DESTROY_AGENT_SUCCESS](state, {response}) {
    console.log(response);
    state.records = response;
    state.count = response.length
  },

  [types.DESTROY_AGENT_FAILURE](state) {
    console.log(state);
  },

  [types.CREATE_AGENT_SUCCESS](state, {response}) {
    state.records = response
  },

  [types.CREATE_AGENT_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_AGENTS_FAILURE](state) {
    state.records = null
  },

}

// actions
export const actions = {
  async fetchRecords({commit}) {
    try {
      const {data} = await axios.get('/api/agents')

      commit(types.FETCH_AGENTS_SUCCESS, {response: data})
    } catch (e) {
      commit(types.FETCH_AGENTS_FAILURE)
    }

  },
  async destroyRecord({commit}, payload) {

    try {
      const {data} = await axios.delete('/api/agents/' + payload.id)
      commit(types.DESTROY_AGENT_SUCCESS, {response: data})
    } catch (e) {
      commit(types.DESTROY_AGENT_FAILURE)
    }
  },
  async createRecord({commit}, payload) {

    try {
      const {data} = await axios.post('/api/agents/' + payload)
      console.log(payload, data);
      //commit(types.CREATE_AGENT_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_AGENT_FAILURE)
    }
  },
}
