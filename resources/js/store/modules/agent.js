// state
import axios from "axios";
import * as types from "../mutation-types";
import {apiDomain} from "../../config";

export const state = {
  path: apiDomain + 'agents',
  response: null,
  records: null,
  record: {}
}

// getters
export const getters = {
  record: state => state.record,
  records: state => state.records,
  response: state => state.response,
}

// mutations
export const mutations = {
  [types.FETCH_AGENTS_SUCCESS](state, {response}) {
    state.response = response;
    state.records = response.data;
  },

  [types.DESTROY_AGENT_SUCCESS](state, {response}) {
    state.response = response;
    state.records = response.data;
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

  [types.FETCH_AGENT_SUCCESS](state, {response}) {
    state.record = response.data;
    console.log(response.data);
  },

  [types.FETCH_AGENT_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_AGENTS_FAILURE](state) {
    state.records = null
  },

}

// actions
export const actions = {
  async fetchRecords({dispatch, commit, rootState}, payload) {
    let url = (payload.page) ? apiDomain + 'agents?page=' + payload.page : apiDomain + 'agents';

    try {
      const {data} = await axios.get(url)
      commit(types.FETCH_AGENTS_SUCCESS, {response: data})

      // Update pagination
      // https://vuex.vuejs.org/guide/modules.html
      dispatch('pagination/update', data, {root: true})

    } catch (e) {
      commit(types.FETCH_AGENTS_FAILURE)
    }
  },
  async fetchRecord({commit}, payload) {

    try {
      const {data} = await axios.get(apiDomain + 'agents/' + payload.id)
      commit(types.FETCH_AGENT_SUCCESS, {response: data})

    } catch (e) {
      commit(types.FETCH_AGENT_FAILURE)
    }
  },
  async destroyRecord({dispatch, commit}, payload) {

    try {
      const {data} = await axios.delete(apiDomain + 'agents/' + payload.id)
      commit(types.DESTROY_AGENT_SUCCESS, {response: data})
      dispatch('pagination/update', data, {root: true})
    } catch (e) {
      commit(types.DESTROY_AGENT_FAILURE)
    }
  },
  async createRecord({commit}, payload) {

    try {
      //commit(types.CREATE_AGENT_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_AGENT_FAILURE)
    }
  },
}
