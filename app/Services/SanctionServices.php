import api from './api';

const sanctionService = {
  // Récupérer toutes les sanctions
  getSanctions: async () => {
    try {
      const response = await api.get('/coach/sanctions');
      return response.data;
    } catch (error) {
      console.error('Erreur getSanctions:', error);
      throw error;
    }
  },

  // Créer une sanction
  createSanction: async (data: {
    stagiaire_id: number;
    niveau: 'avertissement' | 'blame' | 'suspension' | 'exclusion';
    motif: string;
    description: string;
    date_fin_suspension?: string;
  }) => {
    try {
      const response = await api.post('/coach/sanctions', data);
      return response.data;
    } catch (error) {
      console.error('Erreur createSanction:', error);
      throw error;
    }
  },

  // Modifier une sanction
  updateSanction: async (id: number, data: {
    niveau?: 'avertissement' | 'blame' | 'suspension' | 'exclusion';
    motif?: string;
    description?: string;
    date_fin_suspension?: string;
  }) => {
    try {
      const response = await api.put(`/coach/sanctions/${id}`, data);
      return response.data;
    } catch (error) {
      console.error('Erreur updateSanction:', error);
      throw error;
    }
  },

  // Supprimer une sanction
  deleteSanction: async (id: number) => {
    try {
      const response = await api.delete(`/coach/sanctions/${id}`);
      return response.data;
    } catch (error) {
      console.error('Erreur deleteSanction:', error);
      throw error;
    }
  },
};

export default sanctionService;