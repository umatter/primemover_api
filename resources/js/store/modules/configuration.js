// state
import axios from "axios";
import * as types from "../mutation-types";
import {apiDomain} from "../../config";

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
  [types.FETCH_CONFIGURATIONS_SUCCESS](state, {response}) {
    state.records = response.data;
    state.count = response.data.length
  },

  [types.DESTROY_CONFIGURATION_SUCCESS](state, {response}) {
    console.log(response);
    state.records = response;
    state.count = response.length
  },

  [types.DESTROY_CONFIGURATION_FAILURE](state) {
    console.log(state);
  },

  [types.CREATE_CONFIGURATION_SUCCESS](state, {response}) {
    state.records = response
  },

  [types.CREATE_CONFIGURATION_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_CONFIGURATIONS_FAILURE](state) {
    state.records = null
  },

}

// actions
export const actions = {
  async fetchRecords({commit}) {
    try {
      const {data} = await axios.get(apiDomain + 'configurations')

      commit(types.FETCH_CONFIGURATIONS_SUCCESS, {response: data})
    } catch (e) {
      commit(types.FETCH_CONFIGURATIONS_FAILURE)
    }

  },
  async destroyRecord({commit}, payload) {
    //console.log(commit, payload)
    try {
      const {data} = await axios.delete(apiDomain + 'configurations/' + payload.id)
      commit(types.DESTROY_CONFIGURATION_SUCCESS, {response: data})
    } catch (e) {
      commit(types.DESTROY_CONFIGURATION_FAILURE)
    }
  },
  async createRecord({commit}, payload) {

    try {
      //commit(types.CREATE_CONFIGURATION_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_CONFIGURATION_FAILURE)
    }
  },
}
