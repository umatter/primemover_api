// state
import * as types from "../mutation-types";

export const state = {
  links: {
    first: '',
    last: '',
    next: '',
    prev: '',
  },
  meta: {
    current_page: 1,
    from: 1,
    last_page: 1,
    path: '',
    per_page: 1,
    to: 1,
    total: 1,
  },
}

// getters
export const getters = {
  links: state => state.links,
  meta: state => state.meta,
}

// mutations
export const mutations = {
  [types.FETCH_RECORDS_SUCCESS](state, {response}) {

    state.links = response.links;
    state.meta = response.meta;
    console.log(state);
  },

  [types.FETCH_RECORDS_FAILURE](state) {
    console.warn(state);
  },

}

// actions
export const actions = {
  update({commit}, payload) {
    //console.log(payload)
    try {
      commit(types.FETCH_RECORDS_SUCCESS, {response: payload})
    } catch (e) {
      commit(types.FETCH_RECORDS_FAILURE)
    }
  },
}
