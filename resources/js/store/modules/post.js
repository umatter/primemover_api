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
  [types.FETCH_POSTS_SUCCESS](state, {response}) {
    state.records = response.posts;
    state.count = response.posts.length
  },

  [types.DESTROY_POST_SUCCESS](state, {response}) {
    console.log(response);
    state.records = response;
    state.count = response.length
  },

  [types.DESTROY_POST_FAILURE](state) {
    console.log(state);
  },

  [types.CREATE_POST_SUCCESS](state, {response}) {
    state.records = response
  },

  [types.CREATE_POST_FAILURE](state) {
    console.log(state);
  },

  [types.FETCH_POSTS_FAILURE](state) {
    state.records = null
  },

}

// actions
export const actions = {
  async fetchRecords({commit}) {
    try {
      const {data} = await axios.get('/api/posts')

      commit(types.FETCH_POSTS_SUCCESS, {response: data})
    } catch (e) {
      commit(types.FETCH_POSTS_FAILURE)
    }

  },
  async destroyRecord({commit}, payload) {
  //console.log(commit, payload)
    try {
      const {data} = await axios.delete('/api/posts/' + payload.id)
      commit(types.DESTROY_POST_SUCCESS, {response: data})
    } catch (e) {
      commit(types.DESTROY_POST_FAILURE)
    }
  },
  async createRecord({commit}, payload) {

    try {
      const {data} = await axios.post('/api/posts/' + payload)
      console.log(payload, data);
      //commit(types.CREATE_POST_SUCCESS, {response: data})
    } catch (e) {
      //commit(types.CREATE_POST_FAILURE)
    }
  },
}
