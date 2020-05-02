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
  [types.FETCH_QUERIES_SUCCESS](state, {response}) {
    state.records = response.queries;
    state.count = response.queries.length
  },

  [types.DESTROY_QUERY_SUCCESS](state, {response}) {
    console.log(response);
    state.records = response;
    state.count = response.length
  },

  [types.DESTROY_QUERY_FAILURE](state) {
    console.log(state);
  },

  [types.CREATE_QUERY_SUCCESS](state, {response}) {
    state.records = response
  },

  [types.CREATE_QUERY_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_QUERIES_FAILURE](state) {
    state.records = null
  },

}

// actions
export const actions = {
  async fetchRecords({commit}) {
    try {
      const {data} = await axios.get('/api/queries')

      commit(types.FETCH_QUERIES_SUCCESS, {response: data})
    } catch (e) {
      commit(types.FETCH_QUERIES_FAILURE)
    }

  },
  async destroyRecord({commit}, payload) {
  //console.log(commit, payload)
    try {
      const {data} = await axios.delete('/api/queries/' + payload.id)
      commit(types.DESTROY_QUERY_SUCCESS, {response: data})
    } catch (e) {
      commit(types.DESTROY_QUERY_FAILURE)
    }
  },
  async createRecord({commit}, payload) {

    try {
      const {data} = await axios.post('/api/queries/' + payload)
      console.log(payload, data);
      //commit(types.CREATE_QUERY_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_QUERY_FAILURE)
    }
  },
}
