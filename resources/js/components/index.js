import Vue from 'vue'
import Card from './Card'
import DataTable from './DataTable'
import Child from './Child'
import Button from './Button'
import Checkbox from './Checkbox'
import {AlertError, AlertSuccess, HasError} from 'vform'

// Components that are registered globaly.
[
  Card,
  DataTable,
  Child,
  Button,
  Checkbox,
  HasError,
  AlertError,
  AlertSuccess
].forEach(Component => {
  Vue.component(Component.name, Component)
})
