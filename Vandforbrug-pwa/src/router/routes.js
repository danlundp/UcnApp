import FrontPage from '../components/FrontPage'
import Users from '@/components/Users'
import Pricing from '../components/Pricing'
import Login from '../components/auth/Login'
import Logout from '../components/auth/Logout'
import MonthlyMeasurements from '../components/MonthlyMeasurements'
import Dashboard from '../components/Dashboard'
import SavingTips from '../components/SavingTips'

import UserDetails from '../components/UserDetails'

const routes = [
  {
    path: '/',
    name: 'FrontPage',
    component: FrontPage
  },
  {
    path: '/users/',
    component: Users
  },
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: {
      requiresVisitor: true
    }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    props: true,
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/logout',
    name: 'logout',
    component: Logout
  },
  {
    path: '/pricing',
    component: Pricing
  },


  {
    path: '/SavingTips',
    name: 'SavingTips',
    component: SavingTips
  },
  {
    path: '/user/:userId/monthly',
    name: 'monthly',
    component: MonthlyMeasurements
  },
  {
    path: '/userdetails',
    name: 'details',
    component: UserDetails
  }
]

export default routes
