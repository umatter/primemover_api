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
  [types.FETCH_JOBS_SUCCESS](state, {response}) {
    state.records = response.jobs;
    state.count = response.jobs.length
  },

  [types.DESTROY_JOB_SUCCESS](state, {response}) {
    console.log(response);
    state.records = response;
    state.count = response.length
  },

  [types.DESTROY_JOB_FAILURE](state) {
    console.log(state);
  },

  [types.CREATE_JOB_SUCCESS](state, {response}) {
    state.records = response
  },

  [types.CREATE_JOB_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_JOBS_FAILURE](state) {
    state.records = null
  },

}

// actions
export const actions = {
  async fetchRecords({commit}) {
    try {
      const {data} = await axios.get(apiDomain + 'jobs')

      commit(types.FETCH_JOBS_SUCCESS, {response: data})
    } catch (e) {
      commit(types.FETCH_JOBS_FAILURE)
    }

  },
  async destroyRecord({commit}, payload) {
    //console.log(commit, payload)
    try {
      const {data} = await axios.delete(apiDomain + 'jobs/' + payload.id)
      commit(types.DESTROY_JOB_SUCCESS, {response: data})
    } catch (e) {
      commit(types.DESTROY_JOB_FAILURE)
    }
  },
  async createRecord({commit}, payload) {

    try {
      //commit(types.CREATE_JOB_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_JOB_FAILURE)
    }
  },
}
